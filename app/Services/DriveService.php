<?php

namespace App\Services;

use App\Enums\DocumentTypeDriveEnum;
use App\Enums\PermissionTypeDriveEnum;
use App\Exceptions\UploadDocumentException;
use App\Http\Requests\ForceDeleteRequest;
use App\Http\Requests\ForceDeleteTrashRequest;
use App\Http\Requests\StoreAccessPermissionDriveRequest;
use App\Http\Requests\StoreDriveRequest;
use App\Http\Requests\UpdateDriveRequest;
use App\Models\Drive;
use App\Models\DriveFolder;
use App\Models\DrivePermission;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveService
{
    /**
     * Subpasta do módulo dentro da pasta do tenant: tenant<id>/drive/...
     */
    private const BASE_PATH = 'drive';

    public function __construct(protected DriveLogService $driveLogService) {}

    /**
     * Disco de armazenamento (isolado por tenant pelo bootstrapper).
     */
    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(config('bucket.disk'));

        return $disk;
    }

    /**
     * Prefixo (subpasta) do módulo dentro da pasta do tenant.
     */
    private function basePath(): string
    {
        return self::BASE_PATH;
    }

    public function store(StoreDriveRequest $request, Tenant $tenant)
    {
        return $tenant->run(function () use ($request) {
            return DB::transaction(function () use ($request) {

                $folder = DriveFolder::findOrFail($request->validated('folder_id'));

                $baseName = pathinfo($request->validated('documents')[0]->getClientOriginalName(), PATHINFO_FILENAME);

                $counter = 1;

                $disk = $this->disk();

                $documentName = $request->validated('documents')[0]->getClientOriginalName();
                $extension = $request->validated('documents')[0]->getClientOriginalExtension();

                while ($disk->exists($this->basePath().'/'.$folder->getPath().'/'.$documentName)) {
                    $documentName = $baseName." ($counter).".$extension;
                    $counter++;
                }

                $document_path = $this->basePath().'/'.$folder->getPath().'/'.$documentName;

                $drive = Drive::create([
                    'user_id' => $request->validated('user_id'),
                    'drive_folder_id' => $request->validated('folder_id'),
                    'name' => $documentName,
                    'document_path' => $document_path,
                    'document_size' => $request->validated('documents')[0]->getSize(),
                    'document_type' => $request->validated('documents')[0]->extension(),
                    'modified_by' => auth()->user()->id,
                    'modified_at' => Carbon::parse($request->validated('modified_at')[0])->utc(),
                ]);

                try {
                    $this->disk()->putFileAs(
                        $this->basePath().'/'.$folder->getPath(),
                        $request->file('documents')[0],
                        $documentName,
                    );
                } catch (\Throwable $th) {
                    throw new UploadDocumentException('Erro ao tentar fazer upload do documento.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                return $drive;
            });
        });
    }

    /**
     * Serve o documento ao usuário.
     *
     * Em produção (endpoint público) pode redirecionar para uma URL temporária
     * assinada; caso contrário, transmite o arquivo pela aplicação — o que
     * funciona em qualquer ambiente, inclusive com o MinIO do Sail.
     */
    public function download(string $id, Tenant $tenant): RedirectResponse|StreamedResponse
    {
        return $tenant->run(function () use ($id) {
            $drive = Drive::findOrFail($id);

            $disk = $this->disk();

            if (! $disk->exists($drive->document_path)) {
                abort(Response::HTTP_NOT_FOUND, 'Documento não encontrado no armazenamento.');
            }

            if (config('bucket.signed_urls') && $disk->providesTemporaryUrls()) {
                return redirect()->away(
                    $disk->temporaryUrl($drive->document_path, now()->addMinutes((int) config('bucket.url_ttl')))
                );
            }

            return $disk->download($drive->document_path, $drive->name);
        });
    }

    public function update(UpdateDriveRequest $request, Tenant $tenant)
    {
        return $tenant->run(function () use ($request) {
            return DB::transaction(function () use ($request) {

                if ($request->validated('type_drive') == 1) {
                    $drive = DriveFolder::findOrFail($request->validated('id'));
                    $parts = explode('/', $drive->getPath());
                    $path = $drive->getPath();

                    $name = $request->validated('name');

                    // altera o último elemento
                    $parts[count($parts) - 1] = $name;

                    // junta tudo de novo
                    $joinPath = implode('/', $parts);

                    $oldPath = $this->basePath()."/{$path}";
                    $newPath = $this->basePath()."/{$joinPath}";

                    if ($this->disk()->exists($oldPath)) {
                        $this->disk()->move($oldPath, $newPath);
                    }

                    $drive->name = $name;
                    $drive->save();

                    $drive->drives()->where('document_type', $request->validated('drive_type'))->update([
                        'name' => $name,
                        'document_path' => $newPath,
                        'modified_by' => auth()->user()->id,
                        'modified_at' => Carbon::now(),
                    ]);

                    return $drive;
                }

                $drive = Drive::findOrFail($request->validated('id'));
                $path = $drive->driveFolder->getPath();
                $extension = $drive->document_type->getType();
                $name = $request->validated('name').'.'.$extension;

                // Caminhos
                $oldPath = $this->basePath()."/{$path}/{$drive->name}";
                $newPath = $this->basePath()."/{$path}/{$name}";

                // Move se existir
                if ($this->disk()->exists($oldPath)) {
                    $this->disk()->move($oldPath, $newPath);

                    $drive->document_path = $newPath;
                    $drive->name = $name;
                    $drive->modified_by = auth()->user()->id;
                    $drive->modified_at = Carbon::now();
                    $drive->save();
                }

                return $drive;
            });
        });
    }

    public function delete(string $id, Tenant $tenant)
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {

                $loggedInUser = Auth::user()->id;

                $drive = Drive::findOrFail($id);

                $drive->modified_by = $loggedInUser;
                $drive->modified_at = now();

                $drive->save();

                $drive->delete();

                return $drive;
            });
        });
    }

    public function findAll(Tenant $tenant): LazyCollection
    {
        return $tenant->run(function () {

            $user = Auth::user();

            $drives = Drive::with(['driveFolder', 'drivePermissions', 'createdBy', 'modifiedBy'])
                ->whereHas('driveFolder', function ($query) {
                    $query->whereNull('parent_id');
                })->where('document_type', DocumentTypeDriveEnum::FOLDER->value)
                ->orderBy('name', 'asc')
                ->orderBy('created_at', 'desc')
                ->lazy();

            return $drives->map(function ($drive) use ($user) {
                $drive->permission_attrs = $this->getPermissionAttributes($drive, $user);

                return $drive;
            });
        });
    }

    public function findByFolder(string $drive_folder_id, Tenant $tenant): LazyCollection
    {
        return $tenant->run(function () use ($drive_folder_id) {

            $user = Auth::user();

            $drives = Drive::with(['driveFolder', 'drivePermissions', 'createdBy', 'modifiedBy'])
                ->where(function ($query) use ($drive_folder_id) {
                    $query->where('document_type', DocumentTypeDriveEnum::FOLDER->value)
                        ->whereHas('driveFolder', function ($q) use ($drive_folder_id) {
                            $q->where('parent_id', $drive_folder_id);
                        });
                })
                ->orWhere(function ($query) use ($drive_folder_id) {
                    $query->where('drive_folder_id', $drive_folder_id)
                        ->where('document_type', '!=', DocumentTypeDriveEnum::FOLDER->value);
                })
                ->orderByRaw('CASE WHEN document_type = ? THEN 0 ELSE 1 END', [DocumentTypeDriveEnum::FOLDER->value])
                ->orderBy('name', 'asc')
                ->orderBy('created_at', 'desc')
                ->lazy();

            // Adiciona atributos de permissão a cada item
            return $drives->map(function ($drive) use ($user) {
                $drive->permission_attrs = $this->getPermissionAttributes($drive, $user);

                return $drive;
            });
        });
    }

    public function findByTrash(Tenant $tenant): LazyCollection
    {
        return $tenant->run(function () {

            $user = Auth::user();

            $drives = Drive::with(['driveFolder', 'drivePermissions', 'createdBy', 'modifiedBy'])
                ->onlyTrashed()
                ->orderBy('document_type', 'asc')
                ->lazy();

            return $drives->map(function ($drive) use ($user) {
                $drive->permission_attrs = $this->getPermissionAttributes($drive, $user);

                return $drive;
            });
        });
    }

    public function findByTrashFolder(string $drive_folder_id, Tenant $tenant): LazyCollection
    {
        return $tenant->run(function () use ($drive_folder_id) {

            $user = Auth::user();

            $drives = Drive::with(['drivePermissions', 'createdBy', 'modifiedBy'])->withTrashed()
                ->where(function ($query) use ($drive_folder_id) {
                    $query->where('document_type', DocumentTypeDriveEnum::FOLDER->value)
                        ->whereHas('driveFolder', function ($q) use ($drive_folder_id) {
                            $q->where('parent_id', $drive_folder_id);
                        });
                })
                ->orWhere(function ($query) use ($drive_folder_id) {
                    $query->where('drive_folder_id', $drive_folder_id)
                        ->where('document_type', '!=', DocumentTypeDriveEnum::FOLDER->value);
                })
                ->orderByRaw('CASE WHEN document_type = ? THEN 0 ELSE 1 END', [DocumentTypeDriveEnum::FOLDER->value])
                ->orderBy('deleted_at', 'desc')
                ->lazy();

            return $drives->map(function ($drive) use ($user) {
                $drive->permission_attrs = $this->getPermissionAttributes($drive, $user);

                return $drive;
            });
        });
    }

    public function restore(string $id, string $type, Tenant $tenant)
    {
        return $tenant->run(function () use ($id, $type) {
            return DB::transaction(function () use ($id, $type) {

                $drive = null;
                if ($type != DocumentTypeDriveEnum::FOLDER->value) {
                    $drive = Drive::onlyTrashed()->findOrFail($id);
                    $drive->restore();
                }

                if ($type == DocumentTypeDriveEnum::FOLDER->value) {
                    $drive = DriveFolder::withTrashed()->findOrFail($id);
                    $drive->restore();
                    $drive->drives()->withTrashed()->restore();
                }

                return $drive;
            });
        });
    }

    public function forceDelete(ForceDeleteRequest $request, Tenant $tenant)
    {
        return $tenant->run(function () use ($request) {

            $drive = null;

            if ($request->validated('drive_type') != DocumentTypeDriveEnum::FOLDER->value && $request->validated('confirm_delete') == 1) {
                $drive = Drive::onlyTrashed()->findOrFail($request->validated('id')); // Drive

                $documentPath = $drive->document_path;

                DB::transaction(function () use ($drive) {
                    $this->driveLogService->store($drive->toArray());

                    $drive->forceDelete();
                });

                // Remove o arquivo do disco somente após o commit (operação irreversível)
                $this->disk()->delete($documentPath);
            }

            if ($request->validated('drive_type') == DocumentTypeDriveEnum::FOLDER->value && $request->validated('confirm_delete') == 1) {
                $drive = DriveFolder::findOrFail($request->validated('id')); // Pasta

                $folderPath = $this->basePath().'/'.$drive->getPath();

                DB::transaction(function () use ($drive) {
                    $this->driveLogService->store($drive->drives()->withTrashed()->first()->toArray());

                    $drive->forceDelete();
                });

                // Remove o diretório do disco somente após o commit (operação irreversível)
                $this->disk()->deleteDirectory($folderPath);
            }

            return $drive;
        });
    }

    public function deleteSelected(array $ids, Tenant $tenant)
    {
        return $tenant->run(function () use ($ids) {
            return DB::transaction(function () use ($ids) {

                $loggedInUser = Auth::user()->id;

                $drive = null;
                foreach ($ids as $id) {
                    $drive = Drive::findOrFail($id);

                    $drive->modified_by = $loggedInUser;
                    $drive->modified_at = now();

                    $drive->save();

                    $drive->delete();
                }

                return $drive;
            });
        });
    }

    public function clearTrash(ForceDeleteTrashRequest $request, Tenant $tenant)
    {
        return $tenant->run(function () use ($request) {

            $drive = null;

            foreach ($request->validated('selected_drives') as $data) {
                if ($data['drive_type'] != DocumentTypeDriveEnum::FOLDER->value && $request->validated('confirm_delete') == 1) {
                    $drive = Drive::onlyTrashed()->findOrFail($data['id']); // Drive

                    $documentPath = $drive->document_path;

                    DB::transaction(function () use ($drive) {
                        $this->driveLogService->store($drive->toArray());

                        $drive->forceDelete();
                    });

                    // Remove o arquivo do disco somente após o commit (operação irreversível)
                    $this->disk()->delete($documentPath);
                }

                if ($data['drive_type'] == DocumentTypeDriveEnum::FOLDER->value && $request->validated('confirm_delete') == 1) {
                    $drive = DriveFolder::findOrFail($data['id']); // Pasta

                    $folderPath = $this->basePath().'/'.$drive->getPath();

                    DB::transaction(function () use ($drive) {
                        $this->driveLogService->store($drive->drives()->withTrashed()->first()->toArray());

                        $drive->forceDelete();
                    });

                    // Remove o diretório do disco somente após o commit (operação irreversível)
                    $this->disk()->deleteDirectory($folderPath);
                }
            }

            return $drive;
        });
    }

    public function storeAccessPermissions(StoreAccessPermissionDriveRequest $request, Tenant $tenant)
    {
        return $tenant->run(function () use ($request) {
            return DB::transaction(function () use ($request) {

                $drive = Drive::findOrFail($request->validated('drive_id'));

                $getUsers = $request->validated('users');

                $usersWithPermission = $drive->drivePermissions->map(function ($d) {
                    return $d->user_id;
                });

                $userAdmin = [];

                if ($request->validated('permission') == PermissionTypeDriveEnum::SOMENTE_PROPRIETARIO->value) {
                    $userAdmin[] = Auth::user()->id;
                    $users = array_diff($userAdmin, $usersWithPermission->toArray());
                } else {
                    $users = array_diff($getUsers, $usersWithPermission->toArray());
                }

                foreach ($users as $user) {
                    $drive->drivePermissions()->create([
                        'drive_id' => $request->validated('drive_id'),
                        'user_id' => $user,
                        'permission_type' => $request->validated('permission'),
                    ]);
                }

                return true;
            });
        });
    }

    /**
     * Verifica se o usuário pode acessar o drive/pasta/arquivo
     */
    public function userCanAccess(Drive $drive, User $user): bool
    {
        // Proprietário sempre pode
        if ($drive->user_id === $user->id) {
            return true;
        }

        // Garante que drivePermissions é uma collection
        $permissions = $drive->drivePermissions ?? collect();

        // Verifica se existe restrição
        $hasRestriction = $permissions
            ->where('permission_type', PermissionTypeDriveEnum::SOMENTE_PROPRIETARIO)
            ->isNotEmpty();

        // Sem restrição = acesso liberado
        if (! $hasRestriction) {
            return true;
        }

        // Com restrição, verifica se usuário tem acesso explícito
        return $permissions
            ->where('user_id', $user->id)
            ->where('permission_type', PermissionTypeDriveEnum::ACESSO_TOTAL)
            ->isNotEmpty();

    }

    /**
     * Retorna atributos de UI para permissão (disabled, styles, etc)
     */
    public function getPermissionAttributes(Drive $drive, User $user): array
    {
        $userCanAccess = $this->userCanAccess($drive, $user);

        if ($userCanAccess) {
            return [
                'has_access' => true,
                'visible' => '',
                'disable' => false,
                'title' => null,
            ];
        }

        return [
            'has_access' => false,
            'visible' => 'pointer-events: none; opacity: 0.5;',
            'disable' => true,
            'title' => 'Acesso somente para o proprietário',
        ];
    }

    /**
     * Versão otimizada para múltiplos drives (evita N+1)
     */
    public function loadPermissions($drives, User $user): Collection
    {
        return $drives->map(function ($drive) use ($user) {
            $drive->permission_attrs = $this->getPermissionAttributes($drive, $user);

            return $drive;
        });
    }

    public function userAccess(string $driveId, Tenant $tenant)
    {
        return $tenant->run(function () use ($driveId) {

            $drive = Drive::findOrFail($driveId);

            $users = DrivePermission::where('drive_id', $driveId)
                ->with('user:id,name')
                ->get()
                ->map(function ($permission) {
                    return [
                        'id' => $permission->user->id,
                        'name' => $permission->user->name,
                        'tipo_permission' => $permission->permission_type->value,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users,
                'drive' => [
                    'id' => $drive->id,
                    'name' => $drive->name,
                ],
            ]);
        });
    }

    public function removeUserAccess(string $drive_id, string $user_id, Tenant $tenant)
    {

        return $tenant->run(function () use ($drive_id, $user_id) {

            $drive = DrivePermission::where('drive_id', $drive_id)->where('user_id', $user_id)->first();

            return $drive->delete();
        });
    }

    public function search(string $query, Tenant $tenant): LazyCollection
    {
        return $tenant->run(function () use ($query) {

            $user = Auth::user();

            $drives = Drive::with(['driveFolder', 'drivePermissions', 'createdBy', 'modifiedBy'])
                ->where('name', 'like', '%'.$query.'%')
                ->orderByRaw('CASE WHEN document_type = ? THEN 0 ELSE 1 END', [DocumentTypeDriveEnum::FOLDER->value])
                ->orderBy('name', 'asc')
                ->orderBy('created_at', 'desc')
                ->lazy();

            // Adiciona atributos de permissão a cada item
            return $drives->map(function ($drive) use ($user) {
                $drive->permission_attrs = $this->getPermissionAttributes($drive, $user);

                return $drive;
            });
        });
    }
}
