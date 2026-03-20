<?php

namespace App\Models;

use App\Enum\SettingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TenantSetting extends Model
{
    // Não usa created_at
    const CREATED_AT = null;

    protected $fillable = [
        'key',
        'value',
        'type',
        'module',
        'is_public',
        'description',
        'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com usuário que atualizou
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Retorna o valor com casting automático baseado no tipo
     */
    public function getCastedValue(): mixed
    {
        return match($this->type) {
            SettingType::INTEGER => (int) $this->value,
            SettingType::BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            SettingType::DECIMAL => (float) $this->value,
            SettingType::JSON, SettingType::ARRAY => json_decode($this->value, true),
            SettingType::DATE => Carbon::parse($this->value)->format('Y-m-d'),
            SettingType::DATETIME => Carbon::parse($this->value),
            default => $this->value,
        };
    }

    /**
     * Define valor com casting automático
     */
    public function setCastedValue(mixed $value): void
    {
        $this->value = match($this->type) {
            SettingType::BOOLEAN => $value ? 'true' : 'false',
            SettingType::JSON, SettingType::ARRAY => json_encode($value),
            SettingType::DATE, SettingType::DATETIME => $value instanceof Carbon
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
            if (!$setting->updated_by && auth()->check()) {
                $setting->updated_by = auth()->id();
            }
        });
    }

    /**
     * Valida o valor baseado no tipo
     */
    public function validateValue(): void
    {
        $validators = [
            'email' => fn($v) => filter_var($v, FILTER_VALIDATE_EMAIL),
            'url' => fn($v) => filter_var($v, FILTER_VALIDATE_URL),
            'integer' => fn($v) => is_numeric($v),
            'boolean' => fn($v) => in_array(strtolower($v), ['true', 'false', '1', '0']),
            'json' => fn($v) => json_decode($v) !== null,
        ];

        if (isset($validators[$this->type])) {
            if (!$validators[$this->type]($this->value)) {
                throw new \InvalidArgumentException(
                    "Valor inválido para tipo {$this->type}"
                );
            }
        }
    }
}
