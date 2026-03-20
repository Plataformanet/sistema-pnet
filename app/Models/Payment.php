<?php

namespace App\Models;

use App\Enum\PaymentStatus;
use App\Enum\SubscriptionStatus;
use App\Service\MercadoPagoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
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
        'status' => PaymentStatus::class,
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
        return $query->where('status', PaymentStatus::APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    // === MÉTODOS ===

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === PaymentStatus::REJECTED;
    }

    /**
     * Marcar como pago
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => PaymentStatus::APPROVED,
            'paid_at' => now(),
        ]);

        // Ativar/renovar assinatura
        if ($this->subscription) {
            $this->subscription->update([
                'status' => SubscriptionStatus::ACTIVE,
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
            'status' => PaymentStatus::REFUNDED,
            'refunded_at' => now(),
        ]);
    }
}
