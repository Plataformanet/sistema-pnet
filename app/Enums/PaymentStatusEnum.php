<?php

namespace App\Enum;

enum PaymentStatusEnum: string
{
    case PENDING = 'pending';         // Aguardando pagamento
    case APPROVED = 'approved';       // Aprovado
    case AUTHORIZED = 'authorized';   // Autorizado (cartão)
    case IN_PROCESS = 'in_process';   // Em processamento
    case IN_MEDIATION = 'in_mediation'; // Em mediação
    case REJECTED = 'rejected';       // Rejeitado
    case CANCELLED = 'cancelled';     // Cancelado
    case REFUNDED = 'refunded';       // Estornado
    case CHARGED_BACK = 'charged_back'; // Chargeback
}
