<?php

namespace App\Exceptions\RegistryFee;

use Exception;

/**
 * A API de emolumentos recusou o cálculo (erro de negócio). A mensagem é a da
 * API e é exibida no formulário.
 */
class RegistryFeeRejectedException extends Exception {}
