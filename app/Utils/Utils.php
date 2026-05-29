<?php

namespace App\Utils;

use Carbon\Carbon;
use Illuminate\Support\Str;

class Utils
{


    static function format_coin_real($number)
    {
        return number_format($number, 2, ',', '.');
    }

    static function format_coin_sql($number)
    {
        return (str_replace(',', '.', str_replace('.', '', $number)));
    }

    static function data_sql($d)
    {
        $data1    = explode('/', $d);
        $data2[0] = $data1[2];
        $data2[1] = $data1[1];
        $data2[2] = $data1[0];
        $data3    = implode('-', $data2);
        return $data3;
    }

    static function data_brasil($data)
    {
        $ano         = substr($data, -10, 4);
        $mes         = substr($data, -5, 2);
        $dia         = substr($data, -2, 2);
        $data_brasil = "$dia/$mes/$ano";
        return $data_brasil;
    }

    static function status_timeline($concluido, $etapa_iniciada)
    {
        if ($concluido === 1) {
            $status = 'success';
        } elseif ($etapa_iniciada === 1) {
            $status = 'warning';
        } else {
            $status = 'locked';
        }

        return $status;
    }

    static function status_timeline_pdf($concluido, $etapa_iniciada)
    {
        if ($concluido === 1) {
            $status = 'Etapa Concluída';
        } elseif ($etapa_iniciada === 1) {
            $status = 'Em Andamento!';
        } else {
            $status = 'Não Iniciada!';
        }

        return $status;
    }

    static function status_timeline_pdf_icon($concluido, $etapa_iniciada)
    {
        if ($concluido === 1) {
            $status = 'fas fa-check';
        } elseif ($etapa_iniciada === 1) {
            $status = 'fas fa-clock';
        } else {
            $status = 'fas fa-lock';
        }

        return $status;
    }

    static function mes_por_extenso($m)
    {
        switch ((int) $m) {
            case 1:
                return 'Janeiro';
                break;
            case 2:
                return 'Fevereiro';
                break;
            case 3:
                return 'Março';
                break;
            case 4:
                return 'Abril';
                break;
            case 5:
                return 'Maio';
                break;
            case 6:
                return 'Junho';
                break;
            case 7:
                return 'Julho';
                break;
            case 8:
                return 'Agosto';
                break;
            case 9:
                return 'Setembro';
                break;
            case 10:
                return 'Outubro';
                break;
            case 11:
                return 'Novembro';
                break;
            case 12:
                return 'Dezembro';
                break;
            default:
                '---';
        }
    }

    static function formatCpfCnpj($value)
    {
        $CPF_LENGTH = 11;
        $cnpj_cpf   = preg_replace("/\D/", '', $value);

        if (strlen($cnpj_cpf) === $CPF_LENGTH) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
        }

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }

    static function resumeName($name)
    {
        $new_name  = explode(" ", $name);
        $nome      = $new_name[0];
        $sobrenome = '';
        if (isset($new_name[1])) {
            $sobrenome = $new_name[1];
        }
        return $nome . ' ' . $sobrenome;
    }

    static function getSubtraiData($value, $prazo)
    {
        $data1 = date('Y/m/d H:i a');
        $data2 = $value;

        $data3 = strtotime($data1);
        $data4 = strtotime($data2);

        $dataDif = ($data3 - $data4) / (60 * 60 * 24);

        $dataDifInt = (int) $dataDif / (60 * 60 * 24);

        $prazos = $prazo / 24;

        return $prazos;
    }

    static function formatarNumero($valor)
    {
        // Remove os pontos (separadores de milhares) e substitui a vírgula por ponto
        $numeroFormatado = str_replace(['.', ','], ['', '.'], $valor);

        // Converte para inteiro para remover os centavos
        return (int) $numeroFormatado;
    }

    static function normalizarNumero(string $valor)
    {
        // Substitui vírgula por ponto
        $valor = str_replace(',', '.', $valor);

        // Converte para float
        $numero = floatval($valor);

        // Se for inteiro, retorna como int
        if ((int) $numero == $numero) {
            return (int) $numero;
        }

        return $numero;
    }

    function resumirTexto($texto, $limite = 100, $sufixo = '...')
    {
        if (mb_strlen($texto) <= $limite)
            return $texto;

        $textoCortado = mb_substr($texto, 0, $limite);
        return mb_substr($textoCortado, 0, mb_strrpos($textoCortado, ' ')) . $sufixo;
    }

    function anoAnterior()
    {
        return Carbon::now()->subYear()->year;
    }

    function dataAtual()
    {
        $data = Carbon::now();

        return $data->locale('pt_BR')->translatedFormat('F \d\e Y');
    }

    function dataPeriodo(string $periodo, $inicio = null)
    {
        $data = Carbon::parse($inicio === null ? $periodo : $inicio);

        return ucfirst($data->locale('pt_BR')->translatedFormat('F \d\e Y'));
    }

    function anoAtual()
    {
        $data = now()->year;

        return $data;
    }

    function converteSlug(?string $slug = null)
    {

        // substitui o separador por espaço
        $name = str_replace('-', ' ', $slug); // "pasta 01"

        // deixa com a primeira letra maiúscula de cada palavra
        $name = Str::title($name); // "Pasta 01"

        return $name;
    }
}
