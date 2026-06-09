<?php

namespace App\Models;

use App\Enums\SettingTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property SettingTypeEnum|null $type
 * @property string|null $module
 * @property bool $is_public
 * @property string|null $description
 * @property int|null $user_id
 * @property Carbon|null $updated_at
 */
class TenantSetting extends Model
{
    // Não usa created_at
    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'type',
        'module',
        'is_public',
        'description',
    ];

    protected $casts = [
        'type'       => SettingTypeEnum::class,
        'is_public'  => 'boolean',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com usuário que atualizou
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna o valor com casting automático baseado no tipo
     */
    public function getCastedValue(): mixed
    {
        return match ($this->type) {
            SettingTypeEnum::INTEGER => (int) $this->value,
            SettingTypeEnum::BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            SettingTypeEnum::DECIMAL => (float) $this->value,
            SettingTypeEnum::JSON, SettingTypeEnum::ARRAY => json_decode($this->value, true),
            SettingTypeEnum::DATE => Carbon::parse($this->value)->format('Y-m-d'),
            SettingTypeEnum::DATETIME => Carbon::parse($this->value),
            default => $this->value,
        };
    }

    /**
     * Define valor com casting automático
     */
    public function setCastedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            SettingTypeEnum::BOOLEAN => $value ? 'true' : 'false',
            SettingTypeEnum::JSON, SettingTypeEnum::ARRAY => json_encode($value),
            SettingTypeEnum::DATE, SettingTypeEnum::DATETIME => $value instanceof Carbon
            ? $value->toDateTimeString()
            : $value,
            default => (string) $value,
        };
    }

    /**
     * Scope para configurações públicas
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope por módulo
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope configurações globais
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('module');
    }

    /**
     * Validação antes de salvar
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($setting) {
            // Validar baseado no tipo
            $setting->validateValue();

            // Registrar quem está salvando
            if (!$setting->user_id && auth()->check()) {
                $setting->user_id = auth()->id();
            }
        });
    }

    /**
     * Valida o valor baseado no tipo
     */
    public function validateValue(): void
    {
        $validators = [
            'email'   => fn($v) => filter_var($v, FILTER_VALIDATE_EMAIL),
            'url'     => fn($v) => filter_var($v, FILTER_VALIDATE_URL),
            'integer' => fn($v) => is_numeric($v),
            'boolean' => fn($v) => in_array(strtolower($v), ['true', 'false', '1', '0']),
            'json'    => fn($v) => json_decode($v) !== null,
        ];

        $type = $this->type?->value;

        if (isset($validators[$type])) {
            if (!$validators[$type]($this->value)) {
                throw new \InvalidArgumentException(
                    "Valor inválido para tipo {$type}"
                );
            }
        }
    }
}
