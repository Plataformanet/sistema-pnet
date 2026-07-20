<?php

namespace App\Models;

use App\Enums\ContactTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'name_corporatereason',
        'fantasy_name',
        'cpf_cnpj',
        'email',
        'phone',
        'cell_phone',
    ];

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function proponent()
    {
        return $this->hasOne(Proponents::class);
    }

    public function financialContacts()
    {
        return $this->hasMany(FinancialContact::class);
    }

    /**
     * Indica se o contato ainda possui algum papel cadastral vinculado.
     */
    public function hasRemainingRoles(): bool
    {
        return static::withTrashed()
            ->whereKey($this->getKey())
            ->where(fn ($query) => $query
                ->whereHas('client')
                ->orWhereHas('supplier')
                ->orWhereHas('employee')
                ->orWhereHas('proponent'))
            ->exists();
    }

    /**
     * Indica se o contato possui lançamentos financeiros vinculados ao papel informado.
     *
     * O vínculo em `financial_contacts` é por par (contato, tipo), então contas a pagar
     * do fornecedor não impedem a exclusão do mesmo contato como cliente.
     */
    public function hasFinancialEntriesAs(ContactTypeEnum $type): bool
    {
        return $this->financialContacts()
            ->where('type', $type->value)
            ->where(fn ($query) => $query
                ->whereHas('accountsPayable')
                ->orWhereHas('accountsReceivable'))
            ->exists();
    }

    /**
     * Indica se o contato possui qualquer lançamento financeiro vinculado.
     */
    public function hasFinancialEntries(): bool
    {
        return $this->financialContacts()
            ->where(fn ($query) => $query
                ->whereHas('accountsPayable')
                ->orWhereHas('accountsReceivable'))
            ->exists();
    }

    /**
     * Remove o contato e o endereço quando ele deixa de ter qualquer papel cadastral.
     *
     * Contatos com lançamentos financeiros de outros papéis são preservados, pois a
     * exclusão quebraria as referências do módulo financeiro.
     */
    public function deleteIfOrphaned(): void
    {
        if ($this->hasRemainingRoles() || $this->hasFinancialEntries()) {
            return;
        }

        $this->address()->delete();
        $this->delete();
    }
}
