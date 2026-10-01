<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * O usuário informado precisa ter um dos cargos aceitos. Ids em `$alsoAllowed`
 * passam sem o cargo: é o caso do valor já gravado na edição, que continua
 * válido mesmo que o usuário tenha perdido o cargo depois.
 */
class UserHasRole implements ValidationRule
{
    /**
     * @param  array<int, string>  $roles  rótulos dos cargos aceitos
     * @param  array<int, int|string|null>  $alsoAllowed
     */
    public function __construct(
        private array $roles,
        private string $message,
        private array $alsoAllowed = [],
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowedIds = array_map(fn (int|string $id) => (int) $id, array_filter($this->alsoAllowed));

        if (in_array((int) $value, $allowedIds, true)) {
            return;
        }

        $hasRole = User::query()
            ->whereKey($value)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $this->roles))
            ->exists();

        if (! $hasRole) {
            $fail($this->message);
        }
    }
}
