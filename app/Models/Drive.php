<?php

namespace App\Models;

use App\Enums\DocumentTypeDriveEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Drive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'drive_folder_id',
        'name',
        'document_path',
        'document_size',
        'document_type',
        'modified_by',
        'modified_at',
    ];

    protected $casts = [
        'modified_at' => 'datetime',
        'document_type' => DocumentTypeDriveEnum::class,
        'document_size' => 'integer',
    ];

    public function getUrlAttribute()
    {
        if ($this->document_type->value === DocumentTypeDriveEnum::FOLDER->value) {
            return route('tenant.drive.index', [
                'my-drive' => $this->id,
                'parent_id' => $this->driveFolder?->parent_id === null ? $this->driveFolder?->id : $this->driveFolder->parent_id,
                'folder_id' => $this->drive_folder_id,
                'folder' => Str::slug($this->driveFolder?->name),
            ]);
        }

        return route('tenant.drive.download', $this->id);
    }

    public function getUrlTrashAttribute()
    {
        if ($this->document_type->value === DocumentTypeDriveEnum::FOLDER->value) {
            return route('tenant.drive.trash.index', [
                'trash' => $this->id,
                'parent_id' => $this->driveFolder?->parent_id === null ? $this->driveFolder?->id : $this->driveFolder->parent_id,
                'folder_id' => $this->drive_folder_id,
                'folder' => Str::slug($this->driveFolder?->name),
            ]);
        }

        return route('tenant.drive.download', $this->id);
    }

    public function getSizeFormatedAttribute()
    {
        return $this->formatBytes($this->document_size);
    }

    private function formatBytes($bytes)
    {
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        // sempre inteiro
        $formatted = round($bytes);

        if ($formatted !== 0.0) {
            return $formatted.' '.$units[$pow];
        }

        return '---';
    }

    public function getModifiedAtLocalAttribute()
    {
        if (! $this->modified_at) {
            return null;
        }

        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->modified_at->format('Y-m-d H:i:s'),
        )->setTimezone(config('app.timezone'));
    }

    public function getModificationDateAttribute()
    {
        return DocumentTypeDriveEnum::FOLDER->value != $this->document_type->value
            ? $this->modified_at_local->format('d/m/Y')
            : date('d/m/Y', strtotime($this->updated_at));
    }

    public function getModificationDateTittleAttribute()
    {
        return DocumentTypeDriveEnum::FOLDER->value != $this->document_type->value
            ? $this->modified_at_local->format('d/m/Y H:i:s')
            : date('d/m/Y - H:i:s', strtotime($this->updated_at));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driveFolder(): BelongsTo
    {
        return $this->belongsTo(DriveFolder::class);
    }

    public function drivePermissions(): HasMany
    {
        return $this->hasMany(DrivePermission::class);
    }

    /**
     * Usuário que criou o arquivo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Usuário que modificou o arquivo pela última vez
     */
    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
