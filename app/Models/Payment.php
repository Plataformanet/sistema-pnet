<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Service\MercadoPagoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'mp_payment_id',
        'mp_preference_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'description',
        'paid_at',
        'refunded_at',
        'invoice_url',
        'metadata',
        'error_message',
    ];

    protected $casts = [
        'status' => PaymentStatusEnum::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'metadata' => 'array',
    ];

    // === RELACIONAMENTOS ===

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // === SCOPES ===

    public function scopeApproved($query)
    {
        return $query->where('status', PaymentStatusEnum::APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatusEnum::PENDING);
    }

    // === MÉTODOS ===

    public function isPaid(): bool
    {
        return $this->status === PaymentStatusEnum::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatusEnum::PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === PaymentStatusEnum::REJECTED;
    }

    /**
     * Marcar como pago
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => PaymentStatusEnum::APPROVED,
            'paid_at' => now(),
        ]);

        // Ativar/renovar assinatura
        if ($this->subscription) {
            $this->subscription->update([
                'status' => SubscriptionStatusEnum::ACTIVE,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }
    }

    /**
     * Estornar pagamento
     */
    public function refund(): void
    {
        // Chamar API do Mercado Pago para estornar
        app(MercadoPagoService::class)->refundPayment($this->mp_payment_id);

        $this->update([
            'status' => PaymentStatusEnum::REFUNDED,
            'refunded_at' => now(),
        ]);
    }
}
