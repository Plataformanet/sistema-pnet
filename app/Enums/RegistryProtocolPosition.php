<?php

namespace App\Enums;

/**
 * Posição do protocolo de registro no cartório. Os valores inteiros são os
 * mesmos do sistema legado (`protocolo_registros.posicao`), para a migração
 * de dados. "Concluído" não existe no legado.
 */
enum RegistryProtocolPosition: int
{
    case IN_REGISTRY_PG = 1;
    case UNDER_ANALYSIS = 2;
    case REQUIREMENT = 3;
    case AWAITING_BANK_PAYMENT = 4;
    case COMPLETED = 5;

    public function label(): string
    {
        return match ($this) {
            self::IN_REGISTRY_PG => 'Em registro PG',
            self::UNDER_ANALYSIS => 'Em análise',
            self::REQUIREMENT => 'Exigência',
            self::AWAITING_BANK_PAYMENT => 'Em pagamento do banco',
            self::COMPLETED => 'Concluído',
        };
    }

    /**
     * Variante do badge exibido na listagem e na timeline.
     */
    public function color(): string
    {
        return match ($this) {
            self::IN_REGISTRY_PG => 'blue',
            self::UNDER_ANALYSIS => 'amber',
            self::REQUIREMENT => 'orange',
            self::AWAITING_BANK_PAYMENT => 'green',
            self::COMPLETED => 'emerald',
        };
    }

    /**
     * "Em pagamento do banco" e "Concluído" encerram o acompanhamento no
     * cartório: são as posições que a aba "Em aberto" esconde (o legado
     * escondia "Em pagamento do banco" de vez).
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::AWAITING_BANK_PAYMENT, self::COMPLETED], true);
    }

    /**
     * @return array<int, int>
     */
    public static function closedValues(): array
    {
        return array_values(array_map(
            fn (self $position) => $position->value,
            array_filter(self::cases(), fn (self $position) => $position->isClosed()),
        ));
    }

    /**
     * @return array{value: int, label: string, color: string, closed: bool}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'label' => $this->label(), 'color' => $this->color(), 'closed' => $this->isClosed()];
    }

    /**
     * @return array<int, array{value: int, label: string, color: string, closed: bool}>
     */
    public static function options(): array
    {
        return array_map(fn (self $position) => $position->toArray(), self::cases());
    }
}
