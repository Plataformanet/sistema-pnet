<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case AWAITING_PROPERTY = 'awaiting_property';
    case CANCELED = 'canceled';
    case FINISHED = 'finished';
    case RESTRICTED = 'restricted';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nova Proposta',
            self::IN_PROGRESS => 'Em Andamento',
            self::AWAITING_PROPERTY => 'Aguardando Imóvel',
            self::CANCELED => 'Cancelada',
            self::FINISHED => 'Finalizada',
            self::RESTRICTED => 'Com Restrição',
        };
    }

    /**
     * Variante do badge exibido na listagem.
     */
    public function color(): string
    {
        return match ($this) {
            self::NEW => 'blue',
            self::IN_PROGRESS => 'amber',
            self::AWAITING_PROPERTY => 'violet',
            self::CANCELED => 'red',
            self::FINISHED => 'green',
            self::RESTRICTED => 'orange',
        };
    }

    /**
     * Id de `status_propostas` no sistema legado, usado na migração de dados.
     */
    public function legacyId(): int
    {
        return match ($this) {
            self::NEW => 1,
            self::IN_PROGRESS => 2,
            self::AWAITING_PROPERTY => 3,
            self::CANCELED => 4,
            self::FINISHED => 5,
            self::RESTRICTED => 6,
        };
    }

    /**
     * Status que o administrador pode escolher manualmente. "Nova" é o estado
     * inicial e "Finalizada" é atingido só ao concluir a última etapa.
     *
     * @return array<int, self>
     */
    public static function manuallySelectable(): array
    {
        return [self::IN_PROGRESS, self::AWAITING_PROPERTY, self::CANCELED, self::RESTRICTED];
    }

    /**
     * @param  array<int, self>|null  $cases
     * @return array<int, array{value: string, label: string, color: string}>
     */
    public static function options(?array $cases = null): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label(), 'color' => $status->color()],
            $cases ?? self::cases(),
        );
    }
}
