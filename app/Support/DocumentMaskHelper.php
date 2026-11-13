<?php

namespace App\Support;

/**
 * Máscara de CPF/CNPJ para exibição a quem não deve ver o documento inteiro:
 * mantém só os dígitos do meio, que bastam para conferência sem permitir o
 * uso do documento.
 */
final class DocumentMaskHelper
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

    /**
     * Formata o CPF/CNPJ completo para exibição a quem pode ver o documento.
     * O banco guarda só os dígitos; valor de tamanho inesperado volta como está.
     */
    public static function format(string $document): string
    {
        $digits = (string) preg_replace('/\D/', '', $document);

        return match (strlen($digits)) {
            11 => substr($digits, 0, 3).'.'.substr($digits, 3, 3).'.'.substr($digits, 6, 3).'-'.substr($digits, 9, 2),
            14 => substr($digits, 0, 2).'.'.substr($digits, 2, 3).'.'.substr($digits, 5, 3).'/'.substr($digits, 8, 4).'-'.substr($digits, 12, 2),
            default => $document,
        };
    }
}
