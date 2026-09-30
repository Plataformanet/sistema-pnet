<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Enums\ContactTypeEnum;
use App\Exceptions\AccountTotalBelowPaidException;
use App\Exceptions\InactiveContactException;
use App\Exceptions\InstallmentAlreadyPaidException;
use App\Exceptions\InstallmentsTotalMismatchException;
use App\Exceptions\PaidInstallmentLockedException;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\FinancialContact;
use App\Models\Installment;
use App\Models\Supplier;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

abstract class AccountService
{
    protected string $model; // Ex: AccountPayable::class ou AccountReceivable::class

    public function __construct()
    {
        $this->model = $this->getModel();
    }

    abstract protected function getModel(): string;

    /**
     * Papel do contato (fornecedor ou cliente) que recebe os lançamentos deste service.
     */
    abstract protected function contactType(): ContactTypeEnum;

    /**
     * Sentido do pagamento no saldo bancário: -1 para saída (contas a pagar)
     * e +1 para entrada (contas a receber).
     */
    abstract protected function balanceDirection(): int;

    /**
     * Cria o lançamento e suas parcelas. Quando ele já nasce pago, o valor de todas
     * as parcelas é movimentado no saldo da conta bancária na mesma transação.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createAccount(array $data, Tenant $tenant): Model
    {
        return $tenant->run(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $data['financial_contact_id'] = $this->resolveFinancialContactId($data['financial_contact_id'], $this->contactType());
                $data['total_installments'] = $this->installmentsCount($data['payment_condition']);

                $account = $this->model::create($data);
                $status = AccountsEnum::from($data['status']);

                $installments = empty($data['installments'])
                    ? $this->splitIntoInstallments((int) $data['total'], range(1, $data['total_installments']), Carbon::parse($data['due_date']))
                    : collect($data['installments'])->values()->map(fn (array $installment, int $index) => [
                        'installment_number' => $index + 1,
                        'value' => (int) $installment['value'],
                        'due_date' => Carbon::parse($installment['due_date']),
                    ])->all();

                foreach ($installments as $installment) {
                    $account->installments()->create([
                        ...$installment,
                        'description' => $data['description'],
                        'status' => $status,
                        'payment_date' => $status === AccountsEnum::PAID ? $installment['due_date'] : null,
                    ]);
                }

                if ($status === AccountsEnum::PAID) {
                    $this->registerMovement((int) $account->bank_account_id, array_sum(array_column($installments, 'value')));
                }

                return $account;
            });
        });
    }

    /**
     * Atualiza o lançamento preservando o que já foi pago.
     *
     * Se o total ou a quantidade de parcelas mudar, apenas as parcelas em aberto são
     * recriadas com o valor restante. Caso contrário, as parcelas são editadas
     * individualmente. Se a conta bancária mudar, o valor já pago é estornado da
     * conta antiga e lançado na nova.
     *
     * @param  array<string, mixed>  $data
     */
    protected function updateAccount(string $id, array $data, Tenant $tenant): Model
    {
        return $tenant->run(function () use ($id, $data) {
            return DB::transaction(function () use ($id, $data) {
                $account = $this->model::lockForUpdate()->findOrFail($id);

                if (isset($data['financial_contact_id'])) {
                    $data['financial_contact_id'] = $this->resolveFinancialContactId($data['financial_contact_id'], $this->contactType());
                }

                $paidInstallments = $this->lockInstallments($account)->where('status', AccountsEnum::PAID);
                $paidTotal = (int) $paidInstallments->sum('value');

                $data['total_installments'] = max($this->installmentsCount($data['payment_condition']), $paidInstallments->count());

                if ($account->total !== (int) $data['total'] || $account->total_installments !== $data['total_installments']) {
                    $this->rebuildOpenInstallments($account, $data, $paidInstallments);
                } else {
                    $this->updateInstallmentsIndividually($account, $data['installments'] ?? [], (int) $data['total']);
                }

                $this->moveSettledAmount((int) $account->bank_account_id, (int) $data['bank_account_id'], $paidTotal);

                $account->update($data);

                return $account;
            });
        });
    }

    /**
     * Recria as parcelas em aberto distribuindo o valor que ainda falta pagar.
     * As parcelas pagas são mantidas com seus números, e as novas ocupam os
     * números livres.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<int, Installment>  $paidInstallments
     *
     * @throws AccountTotalBelowPaidException
     * @throws InstallmentsTotalMismatchException
     */
    private function rebuildOpenInstallments(Model $account, array $data, Collection $paidInstallments): void
    {
        $total = (int) $data['total'];
        $paidTotal = (int) $paidInstallments->sum('value');

        if ($total < $paidTotal) {
            throw new AccountTotalBelowPaidException($paidTotal);
        }

        $account->installments()->where('status', '!=', AccountsEnum::PAID)->delete();

        $remaining = $total - $paidTotal;

        if ($remaining === 0) {
            return;
        }

        $openNumbers = array_values(array_diff(
            range(1, $data['total_installments']),
            $paidInstallments->pluck('installment_number')->all(),
        ));

        if ($openNumbers === []) {
            throw new InstallmentsTotalMismatchException($paidTotal, $total);
        }

        foreach ($this->splitIntoInstallments($remaining, $openNumbers, Carbon::parse($data['due_date'])) as $installment) {
            $account->installments()->create([
                ...$installment,
                'description' => $data['description'],
                'status' => AccountsEnum::OPEN,
                'payment_date' => null,
            ]);
        }
    }

    /**
     * Aplica o valor e o vencimento editados em cada parcela. Parcelas pagas não
     * podem ter o valor alterado, e a soma final precisa continuar igual ao total.
     *
     * @param  array<int, array{installment_id?: int|null, value: int, due_date: string}>  $installments
     *
     * @throws PaidInstallmentLockedException
     * @throws InstallmentsTotalMismatchException
     */
    private function updateInstallmentsIndividually(Model $account, array $installments, int $total): void
    {
        $editedInstallments = array_filter($installments, fn (array $installment) => ! empty($installment['installment_id']));

        if ($editedInstallments === []) {
            return;
        }

        $currentInstallments = $account->installments()->get()->keyBy('id');

        foreach ($editedInstallments as $edited) {
            $installment = $currentInstallments->get($edited['installment_id']);

            if ($installment === null) {
                continue;
            }

            if ($installment->status === AccountsEnum::PAID && $installment->value !== (int) $edited['value']) {
                throw new PaidInstallmentLockedException;
            }

            $installment->update([
                'value' => $edited['value'],
                'due_date' => $edited['due_date'],
            ]);
        }

        $installmentsTotal = (int) $account->installments()->sum('value');

        if ($installmentsTotal !== $total) {
            throw new InstallmentsTotalMismatchException($installmentsTotal, $total);
        }
    }

    /**
     * Divide um valor em centavos entre os números de parcela informados, sem
     * perder centavos: a diferença do arredondamento fica na última parcela. O
     * vencimento da parcela N é o primeiro vencimento deslocado N-1 meses.
     *
     * @param  list<int>  $numbers
     * @return list<array{installment_number: int, value: int, due_date: Carbon}>
     */
    private function splitIntoInstallments(int $amount, array $numbers, Carbon $firstDueDate): array
    {
        $count = count($numbers);
        $baseValue = intdiv($amount, $count);

        return array_map(fn (int $number, int $position) => [
            'installment_number' => $number,
            'value' => $position === $count - 1 ? $amount - $baseValue * ($count - 1) : $baseValue,
            'due_date' => $firstDueDate->copy()->addMonthsNoOverflow($number - 1),
        ], $numbers, array_keys($numbers));
    }

    /**
     * Carrega e trava todas as parcelas do lançamento até o fim da transação, para
     * que uma baixa simultânea não altere o valor pago enquanto o lançamento é
     * editado ou excluído.
     *
     * @return Collection<int, Installment>
     */
    private function lockInstallments(Model $account): Collection
    {
        return $account->installments()->lockForUpdate()->get();
    }

    private function installmentsCount(string $paymentCondition): int
    {
        return $paymentCondition === 'a-vista' ? 1 : max(1, (int) $paymentCondition);
    }

    /**
     * Parcelas que pertencem ao tipo de lançamento deste service, para que a rota
     * de contas a pagar nunca altere uma parcela de contas a receber (e vice-versa).
     *
     * @return Builder<Installment>
     */
    private function installmentsOfThisType(): Builder
    {
        return Installment::where('installmentable_type', (new $this->model)->getMorphClass());
    }

    /**
     * Lança no saldo da conta bancária o pagamento de um valor, no sentido do
     * service (saída em contas a pagar, entrada em contas a receber).
     */
    private function registerMovement(int $bankAccountId, int $amount): void
    {
        $this->applyToBalance($bankAccountId, $this->balanceDirection() * $amount);
    }

    /**
     * Estorna do saldo da conta bancária um pagamento registrado anteriormente.
     */
    private function reverseMovement(int $bankAccountId, int $amount): void
    {
        $this->applyToBalance($bankAccountId, -$this->balanceDirection() * $amount);
    }

    /**
     * Transfere o valor já pago de uma conta bancária para outra quando o
     * lançamento troca de conta.
     */
    private function moveSettledAmount(int $fromBankAccountId, int $toBankAccountId, int $paidTotal): void
    {
        if ($fromBankAccountId === $toBankAccountId || $paidTotal === 0) {
            return;
        }

        $this->reverseMovement($fromBankAccountId, $paidTotal);
        $this->registerMovement($toBankAccountId, $paidTotal);
    }

    /**
     * Soma o valor ao saldo em um único UPDATE executado pelo banco. Assim duas
     * movimentações simultâneas na mesma conta não sobrescrevem uma à outra.
     */
    private function applyToBalance(int $bankAccountId, int $signedAmount): void
    {
        if ($signedAmount === 0) {
            return;
        }

        $updatedRows = BankAccount::whereKey($bankAccountId)->update([
            'current_balance' => DB::raw('COALESCE(current_balance, 0) + '.$signedAmount),
        ]);

        if ($updatedRows === 0) {
            throw (new ModelNotFoundException)->setModel(BankAccount::class, [$bankAccountId]);
        }
    }

    /**
     * Resolve (ou cria) o financial_contact correspondente ao contato selecionado
     * no formulário e devolve o id da tabela financial_contacts para o FK.
     *
     * @throws InactiveContactException quando o papel do contato está inativo.
     */
    protected function resolveFinancialContactId(int $contactId, ContactTypeEnum $type): int
    {
        $this->assertContactCanReceiveEntry($contactId, $type);

        return FinancialContact::firstOrCreate([
            'contact_id' => $contactId,
            'type' => $type->value,
        ])->id;
    }

    /**
     * Impede que um cliente ou fornecedor inativo receba novos lançamentos.
     *
     * A checagem é por papel: só bloqueia quando existe a linha do papel e ela
     * está inativa. Contatos sem o papel cadastrado seguem o fluxo normal.
     *
     * @throws InactiveContactException
     */
    protected function assertContactCanReceiveEntry(int $contactId, ContactTypeEnum $type): void
    {
        $roleModel = match ($type) {
            ContactTypeEnum::CLIENT => Client::class,
            ContactTypeEnum::SUPPLIER => Supplier::class,
            ContactTypeEnum::EMPLOYEE => Employee::class,
        };

        $isInactive = $roleModel::where('contact_id', $contactId)
            ->where('active', false)
            ->exists();

        if ($isInactive) {
            throw new InactiveContactException($type);
        }
    }

    public function findAll($request, string $periodo, Tenant $tenant)
    {
        return $tenant->run(function () use ($request, $periodo) {
            $inicio = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();

            $fim = $request->query('fim')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->endOfMonth();

            $hoje = null;
            if ($request->query('status') === 'vencem-hoje' || $request->query('status') === 'vencidos') {
                $hoje = now()->startOfDay();
            }

            return $this->model::when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                $query->whereHas('bankAccount', function (Builder $query) use ($request) {
                    $query->where('bank_account_id', $request->query('conta_id'));
                });
            })
                ->when($request->query('conta_id') === null && $request->query('categoria_id') === null, function (Builder $query) {
                    $query->whereHas('bankAccount', function (Builder $query) {
                        $query->where('main_account', 1);
                    });
                })
                ->when($request->query('search'), function (Builder $query) use ($request) {
                    $search = $request->query('search');
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('description', 'like', "%{$search}%")
                            ->orWhereHas('financialContact.contact', function (Builder $query) use ($search) {
                                $query->where('name_corporatereason', 'like', "%{$search}%")
                                    ->orWhere('fantasy_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('installments', function (Builder $query) use ($search) {
                                $query->where('description', 'like', "%{$search}%");
                            });

                        if (is_numeric($search)) {
                            $value = (float) $search;
                            $margin = 100;
                            $query->orWhereBetween('total', [$value - $margin, $value + $margin])
                                ->orWhereHas('installments', function (Builder $query) use ($value, $margin) {
                                    $query->whereBetween('value', [$value - $margin, $value + $margin]);
                                });
                        }
                    });
                })
                ->when($request->filled('categoria_id'), function (Builder $query) use ($request) {
                    $query->where('financial_category_id', $request->query('categoria_id'));
                })
                ->whereHas('installments', function (Builder $query) use ($inicio, $fim, $request, $hoje) {
                    $query->whereBetween('due_date', [$inicio, $fim])
                        ->when($request->query('status') === 'pago', function (Builder $query) {
                            $query->where('status', AccountsEnum::PAID->value);
                        })
                        ->when($request->query('status') === 'a-vencer', function (Builder $query) {
                            $query->where('status', AccountsEnum::OPEN->value)
                                ->whereDate('due_date', '>=', Carbon::today());
                        })
                        ->when($request->query('status') === 'vencem-hoje', function (Builder $query) use ($hoje) {
                            $query->whereDate('due_date', $hoje)
                                ->where('status', AccountsEnum::OPEN->value);
                        })
                        ->when($request->query('status') === 'vencidos', function (Builder $query) use ($hoje) {
                            $query->whereDate('due_date', '<', $hoje)
                                ->where('status', AccountsEnum::OPEN->value);
                        });
                })->with([
                    'bankAccount',
                    'financialCategory',
                    'financialSubcategory',
                    'installments' => function ($query) use ($inicio, $fim, $request, $hoje) {
                        $query->whereBetween('due_date', [$inicio, $fim])
                            ->when($request->query('status') === 'pago', function (Builder $query) {
                                $query->where('status', AccountsEnum::PAID->value);
                            })
                            ->when($request->query('status') === 'a-vencer', function (Builder $query) {
                                $query->where('status', AccountsEnum::OPEN->value)
                                    ->whereDate('due_date', '>=', Carbon::today());
                            })
                            ->when($request->query('status') === 'vencem-hoje', function (Builder $query) use ($hoje) {
                                $query->whereDate('due_date', $hoje)
                                    ->where('status', AccountsEnum::OPEN->value);
                            })
                            ->when($request->query('status') === 'vencidos', function (Builder $query) use ($hoje) {
                                $query->whereDate('due_date', '<', $hoje)
                                    ->where('status', AccountsEnum::OPEN->value);
                            });
                    },
                ])->orderByDesc('id')
                ->paginate($request->query('quantidade', 10))
                ->appends($request->all());
        });
    }

    /**
     * Exclui o lançamento e suas parcelas, estornando do saldo bancário o valor
     * das parcelas que já tinham sido pagas.
     */
    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {
                $account = $this->model::lockForUpdate()->findOrFail($id);

                $paidTotal = (int) $this->lockInstallments($account)->where('status', AccountsEnum::PAID)->sum('value');

                $this->reverseMovement((int) $account->bank_account_id, $paidTotal);

                $account->installments()->delete();

                return $account->delete();
            });
        });
    }

    /**
     * Dá baixa na parcela e movimenta o saldo da conta bancária do lançamento.
     *
     * A parcela é travada (`lockForUpdate`) antes da checagem de status, então
     * duas requisições simultâneas não conseguem pagar a mesma parcela duas vezes.
     *
     * @throws InstallmentAlreadyPaidException
     */
    public function updateInstallment(string $id, Tenant $tenant): bool
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {
                $installment = $this->installmentsOfThisType()->lockForUpdate()->findOrFail($id);

                if ($installment->status === AccountsEnum::PAID) {
                    throw new InstallmentAlreadyPaidException;
                }

                $account = $this->model::findOrFail($installment->installmentable_id);

                $this->registerMovement((int) $account->bank_account_id, $installment->value);

                $installment->payment_date = Carbon::now();
                $installment->status = AccountsEnum::PAID;

                return $installment->save();
            });
        });
    }

    public function paymentConditions()
    {
        return [
            '1' => '1x',
            '2' => '2x',
            '3' => '3x',
            '4' => '4x',
            '5' => '5x',
            '6' => '6x',
            '7' => '7x',
            '8' => '8x',
            '9' => '9x',
            '10' => '10x',
            '11' => '11x',
            '12' => '12x',
        ];
    }

    public function totalPeriod($request, string $period, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            $start = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('fim')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            $query = Installment::whereBetween('due_date', [$start, $end])
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                        ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                            $query->where('bank_account_id', $request->query('conta_id'));
                        })
                        ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                            $query->where('bank_account_id', $bankAccountId);
                        });
                });

            return $query->sum('value');
        });
    }

    public function totalPaid($request, string $periodo, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $periodo, $bankAccountId) {
            $start = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();

            $end = $request->query('fim')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->endOfMonth();

            $query = Installment::where('status', AccountsEnum::PAID->value)
                ->whereBetween('due_date', [$start, $end])
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                        ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                            $query->where('bank_account_id', $request->query('conta_id'));
                        })
                        ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                            $query->where('bank_account_id', $bankAccountId);
                        });
                });

            return $query->sum('value');
        });
    }

    public function totalToDue($request, int $days, string $period, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            $statusOpen = AccountsEnum::OPEN->value;
            $today = Carbon::today();

            if ($request->query('inicio') && $request->query('fim')) {
                $start = Carbon::parse($request->query('inicio'))->startOfDay();
                $end = Carbon::parse($request->query('fim'))->endOfDay();
            } else {
                $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
                $end = Carbon::createFromFormat('Y-m', $period)->endOfMonth();
            }

            // We only want outstanding items, so due_date must be >= today
            $start = $today->max($start);

            // If start is after end, return 0
            if ($start->gt($end)) {
                return 0;
            }

            $query = Installment::where('status', $statusOpen)
                ->whereBetween('due_date', [$start, $end])
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->filled('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                        ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                            $query->where('bank_account_id', $request->query('conta_id'));
                        })
                        ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                            $query->where('bank_account_id', $bankAccountId);
                        });
                });

            return $query->sum('value');
        });
    }

    public function totalDueToday($request, string $period, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            $statusOpen = AccountsEnum::OPEN->value;
            $today = now()->startOfDay();

            if ($request->query('inicio') && $request->query('fim')) {
                $intervalStart = Carbon::parse($request->query('inicio'))->startOfDay();
                $intervalEnd = Carbon::parse($request->query('fim'))->endOfDay();

                if ($today->lt($intervalStart) || $today->gt($intervalEnd)) {
                    return 0;
                }
            } else {
                $monthYear = Carbon::createFromFormat('Y-m', $period);
                if ($monthYear->format('Y-m') !== now()->format('Y-m')) {
                    return 0;
                }
            }

            $query = Installment::where('status', $statusOpen)
                ->whereDate('due_date', $today)
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                        ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                            $query->where('bank_account_id', $request->query('conta_id'));
                        })
                        ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                            $query->where('bank_account_id', $bankAccountId);
                        });
                });

            return $query->sum('value');
        });
    }

    public function totalOverdue($request, string $period, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            $statusOpen = AccountsEnum::OPEN->value;
            $today = Carbon::today();

            if ($request->query('inicio') && $request->query('fim')) {
                $start = Carbon::parse($request->query('inicio'))->startOfDay();
                $end = Carbon::parse($request->query('fim'))->endOfDay();
            } else {
                $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
                $end = Carbon::createFromFormat('Y-m', $period)->endOfMonth();
            }

            $query = Installment::where('status', $statusOpen)
                ->whereBetween('due_date', [$start, $end])
                ->whereDate('due_date', '<', $today)
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                        ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                            $query->where('bank_account_id', $request->query('conta_id'));
                        })
                        ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                            $query->where('bank_account_id', $bankAccountId);
                        });
                });

            return $query->sum('value');
        });
    }

    public function search($request, string $period, Tenant $tenant)
    {
        return $tenant->run(function () use ($request, $period) {
            $start = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('fim ')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            return Installment::with('installmentable')->where(function ($query) use ($request) {
                $this->applySearchFilter($query, $request->query('search'));
            })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($start, $end) {
                $query->whereBetween('due_date', [$start, $end]);
            })->paginate($request->query('quantidade', 10))
                ->appends([
                    'periodo' => $request->query('periodo'),
                    'quantidade' => $request->query('quantidade'),
                    'inicio' => $request->query('inicio'),
                    'fim' => $request->query('fim'),
                    'categoria_id' => $request->query('categoria_id'),
                    'tipo_conta' => $request->query('tipo_conta'),
                    'search' => $request->query('search'),
                ]);
        });
    }

    public function searchContact($request)
    {
        $search = $request->query('search');
        $type = $request->query('type');

        $query = Contact::select('id', 'name_corporatereason');

        if ($type === 'client') {
            $query->whereHas('client', fn (Builder $q) => $q->where('active', true));
        } elseif ($type === 'supplier') {
            $query->whereHas('supplier', fn (Builder $q) => $q->where('active', true));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_corporatereason', 'like', "%{$search}%")
                    ->orWhere('fantasy_name', 'like', "%{$search}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$search}%");
            });
        }

        $contacts = $query->limit(15)->get();

        return $contacts;
    }

    protected function applySearchFilter(Builder $query, ?string $search)
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhereHasMorph('installmentable', [$this->model], function (Builder $q) use ($search) {
                    $q->whereHas('financialContact.contact', function (Builder $q) use ($search) {
                        $q->where('name_corporatereason', 'like', "%{$search}%")
                            ->orWhere('fantasy_name', 'like', "%{$search}%");
                    });
                });

            if (is_numeric($search)) {
                $value = (float) $search;
                $margin = 100;
                $q->orWhereBetween('value', [$value - $margin, $value + $margin]);
            }
        });
    }
}
