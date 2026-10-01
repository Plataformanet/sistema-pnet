<?php

namespace App\Exceptions;

use Exception;

/**
 * Operação que exige orçamento em aberto (não convertido e dentro da validade).
 */
class QuoteNotOpenException extends Exception {}
