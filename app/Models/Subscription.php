<?php

namespace App\Models;

use App\Enum\SubscriptionStatus;
use App\Service\MercadoPagoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'mp_subscription_id',
        'mp_preapproval_id',
        'status',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'cancelled_at',
        'ends_at',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // === RELACIONAMENTOS ===

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // === SCOPES ===

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopePastDue($query)
    {
        return $query->where('status', SubscriptionStatus::PAST_DUE);
    }

    // === MÉTODOS DE VERIFICAÇÃO ===

    public function isActive(): bool
    {
        return $this->status->isActive()
            && (!$this->ends_at || $this->ends_at->isFuture());
    }

    public function onTrial(): bool
    {
        return $this->status === SubscriptionStatus::TRIALING
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function onGracePeriod(): bool
    {
        return $this->cancelled_at
            && $this->ends_at
            && $this->ends_at->isFuture();
    }

    public function pastDue(): bool
    {
        return $this->status === SubscriptionStatus::PAST_DUE;
    }

    // === AÇÕES ===

    /**
     * Cancelar assinatura
     */
    public function cancel(bool $immediately = false): void
    {
        $this->update([
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
            'ends_at' => $immediately ? now() : $this->current_period_end,
        ]);

        // Cancelar no Mercado Pago
        if ($this->mp_subscription_id) {
            app(MercadoPagoService::class)->cancelSubscription($this->mp_subscription_id);
        }
    }

    /**
     * Reativar assinatura cancelada
     */
    public function resume(): void
    {
        if (!$this->onGracePeriod()) {
            throw new \Exception('Assinatura não pode ser reativada');
        }

        $this->update([
            'status' => SubscriptionStatus::ACTIVE,
            'cancelled_at' => null,
            'ends_at' => null,
        ]);
    }

    /**
     * Pausar assinatura
     */
    public function pause(): void
    {
        $this->update([
            'status' => SubscriptionStatus::PAUSED,
        ]);
    }

    /**
     * Trocar de plano
     */
    public function swapPlan(Plan $newPlan, bool $prorate = true): void
    {
        $oldPlan = $this->plan;

        $this->update(['plan_id' => $newPlan->id]);

        // Calcular prorata se necessário
        if ($prorate) {
            $this->calculateProration($oldPlan, $newPlan);
        }

        // Atualizar módulos do tenant
        $this->tenant->syncModulesFromPlan($newPlan);
    }

    /**
     * Adicionar add-on (módulo extra)
     */
    public function addAddon(Module $module, float $price): SubscriptionItem
    {
        return $this->items()->create([
            'module_id' => $module->id,
            'description' => "Módulo {$module->name}",
            'quantity' => 1,
            'unit_price' => $price,
            'total_price' => $price,
        ]);
    }

    /**
     * Calcular valor total da assinatura
     */
    public function calculateTotal(): float
    {
        $planPrice = $this->plan->price;
        $addonsTotal = $this->items->sum('total_price');

        return $planPrice + $addonsTotal;
    }
}
