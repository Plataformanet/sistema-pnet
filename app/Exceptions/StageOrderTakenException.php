<?php

namespace App\Exceptions;

use Exception;

/**
 * A ordem foi ocupada por outra etapa ativa da mesma timeline entre a
 * validação e a gravação (cadastros simultâneos).
 */
class StageOrderTakenException extends Exception {}
