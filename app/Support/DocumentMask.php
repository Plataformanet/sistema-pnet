<?php

namespace App\Support;

/**
 * Máscara de CPF/CNPJ para exibição a quem não deve ver o documento inteiro:
 * mantém só os dígitos do meio, que bastam para conferência sem permitir o
 * uso do documento.
 */
final class DocumentMask
{
    public static function mask(string $document): string
    {
        $digits = (string) preg_replace('/\D/', '', $document);

        return match (strlen($digits)) {
            11 => '***.'.substr($digits, 3, 3).'.'.substr($digits, 6, 3).'-**',
            14 => '**.'.substr($digits, 2, 3).'.'.substr($digits, 5, 3).'/****-**',
            default => '***',
        };
    }
}
