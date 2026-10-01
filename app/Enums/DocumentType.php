<?php

namespace App\Enums;

enum DocumentType: string
{
    case ID_DOCUMENT = 'id_document';
    case MARITAL_STATUS_CERTIFICATE = 'marital_status_certificate';
    case PROOF_OF_ADDRESS = 'proof_of_address';
    case PROOF_OF_INCOME = 'proof_of_income';
    case INCOME_TAX_RETURN = 'income_tax_return';
    case WORK_CARD = 'work_card';
    case SIMULATION = 'simulation';
    case CREDIT_ACCOUNT_DATA = 'credit_account_data';
    case PROPERTY_REGISTRATION = 'property_registration';
    case IPTU_COVER = 'iptu_cover';
    case ART = 'art';
    case SCPO = 'scpo';
    case OCCUPANCY_PERMIT = 'occupancy_permit';
    case BUILDING_PERMIT = 'building_permit';
    case FEE_ESTIMATE = 'fee_estimate';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ID_DOCUMENT => 'RG/CNH',
            self::MARITAL_STATUS_CERTIFICATE => 'Certidão de estado civil',
            self::PROOF_OF_ADDRESS => 'Comprovante de endereço',
            self::PROOF_OF_INCOME => 'Documento de renda (3 últimos)',
            self::INCOME_TAX_RETURN => 'IRPF',
            self::WORK_CARD => 'CTPS',
            self::SIMULATION => 'Simulação',
            self::CREDIT_ACCOUNT_DATA => 'Dados da conta de crédito',
            self::PROPERTY_REGISTRATION => 'Matrícula',
            self::IPTU_COVER => 'Capa do IPTU',
            self::ART => 'ART',
            self::SCPO => 'SCPO',
            self::OCCUPANCY_PERMIT => 'Habite-se',
            self::BUILDING_PERMIT => 'Alvará',
            self::FEE_ESTIMATE => 'Emolumento',
            self::OTHER => 'Outros',
        };
    }

    /**
     * Tipos aceitos para o dono do documento (e, no vendedor, para PF/PJ).
     *
     * @return array<int, self>
     */
    public static function forOwner(DocumentOwner $owner, ?PersonType $personType = null): array
    {
        return match ($owner) {
            DocumentOwner::BUYER => [
                self::ID_DOCUMENT,
                self::MARITAL_STATUS_CERTIFICATE,
                self::PROOF_OF_ADDRESS,
                self::PROOF_OF_INCOME,
                self::INCOME_TAX_RETURN,
                self::WORK_CARD,
                self::SIMULATION,
                self::OTHER,
            ],
            DocumentOwner::SELLER => $personType === PersonType::COMPANY
                ? [
                    self::PROPERTY_REGISTRATION,
                    self::ART,
                    self::SCPO,
                    self::OCCUPANCY_PERMIT,
                    self::BUILDING_PERMIT,
                    self::OTHER,
                ]
                : [
                    self::ID_DOCUMENT,
                    self::MARITAL_STATUS_CERTIFICATE,
                    self::PROOF_OF_ADDRESS,
                    self::CREDIT_ACCOUNT_DATA,
                    self::PROPERTY_REGISTRATION,
                    self::IPTU_COVER,
                    self::OTHER,
                ],
            DocumentOwner::PROPERTY => [
                self::CREDIT_ACCOUNT_DATA,
                self::PROPERTY_REGISTRATION,
                self::IPTU_COVER,
                self::OTHER,
            ],
            DocumentOwner::GENERAL => [self::FEE_ESTIMATE, self::OTHER],
            DocumentOwner::STAGE => [self::OTHER],
        };
    }

    /**
     * Tipos obrigatórios do checklist de cada dono.
     *
     * @return array<int, self>
     */
    public static function requiredFor(DocumentOwner $owner, ?PersonType $personType = null): array
    {
        return match ($owner) {
            DocumentOwner::BUYER => [
                self::ID_DOCUMENT,
                self::MARITAL_STATUS_CERTIFICATE,
                self::PROOF_OF_ADDRESS,
                self::PROOF_OF_INCOME,
                self::WORK_CARD,
            ],
            DocumentOwner::SELLER => $personType === PersonType::COMPANY
                ? [
                    self::PROPERTY_REGISTRATION,
                    self::ART,
                    self::SCPO,
                    self::OCCUPANCY_PERMIT,
                    self::BUILDING_PERMIT,
                ]
                : [
                    self::ID_DOCUMENT,
                    self::MARITAL_STATUS_CERTIFICATE,
                    self::CREDIT_ACCOUNT_DATA,
                    self::PROPERTY_REGISTRATION,
                    self::IPTU_COVER,
                ],
            DocumentOwner::PROPERTY => [
                self::CREDIT_ACCOUNT_DATA,
                self::PROPERTY_REGISTRATION,
                self::IPTU_COVER,
            ],
            DocumentOwner::STAGE, DocumentOwner::GENERAL => [],
        };
    }

    public function isRequiredFor(DocumentOwner $owner, ?PersonType $personType = null): bool
    {
        return in_array($this, self::requiredFor($owner, $personType), true);
    }

    /**
     * @param  array<int, self>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(array $cases): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], $cases);
    }
}
