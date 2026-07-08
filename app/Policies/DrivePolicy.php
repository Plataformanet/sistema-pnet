<?php

namespace App\Policies;

use App\Enums\DocumentTypeDriveEnum;
use App\Models\Drive;
use App\Models\DriveFolder;
use App\Models\User;
use App\Services\DriveService;

class DrivePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct(private DriveService $driveService) {}

    public function viewFolder(User $user, DriveFolder $folder): bool
    {
        $driveParent = Drive::where('drive_folder_id', $folder->id)
            ->where('document_type', DocumentTypeDriveEnum::FOLDER)
            ->first();

        if (! $driveParent) {
            return true;
        }

        if (! $this->driveService->userCanAccess($driveParent, $user)) {
            abort(403, 'Você não tem permissão para acessar esta pasta');
        }

        return true;
    }
}
