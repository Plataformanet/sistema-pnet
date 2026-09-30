<?php

namespace App\Exceptions;

use Exception;

/**
 * Base das regras de negócio que bloqueiam uma operação de lançamento financeiro
 * (baixa, edição ou exclusão). Os controllers capturam esta classe para exibir a
 * mensagem como aviso ao usuário, em vez de um erro genérico.
 */
abstract class FinancialEntryException extends Exception {}
