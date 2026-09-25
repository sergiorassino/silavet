<?php

namespace App\Support\Pdf;

/**
 * Superíndices y subíndices Unicode para TCPDF.
 *
 * Arial no incluye todo el bloque (falta, entre otros, ⁻ U+207B). TCPDF dibuja
 * el glifo ausente como signo de interrogación. Se convierten a <sup>/<sub>
 * solo al imprimir; el texto sin esos caracteres no se altera.
 */
final class TcpdfTextoSuperindice
{
    /** @var array<string, string> */
    private const SUPER = [
        '⁰' => '0',
        '¹' => '1',
        '²' => '2',
        '³' => '3',
        '⁴' => '4',
        '⁵' => '5',
        '⁶' => '6',
        '⁷' => '7',
        '⁸' => '8',
        '⁹' => '9',
        '⁺' => '+',
        '⁻' => '-',
        '⁼' => '=',
        '⁽' => '(',
        '⁾' => ')',
        'ⁿ' => 'n',
        'ⁱ' => 'i',
    ];

    /** @var array<string, string> */
    private const SUB = [
        '₀' => '0',
        '₁' => '1',
        '₂' => '2',
        '₃' => '3',
        '₄' => '4',
        '₅' => '5',
        '₆' => '6',
        '₇' => '7',
        '₈' => '8',
        '₉' => '9',
        '₊' => '+',
        '₋' => '-',
        '₌' => '=',
        '₍' => '(',
        '₎' => ')',
        'ₐ' => 'a',
        'ₑ' => 'e',
        'ₒ' => 'o',
        'ₓ' => 'x',
        'ₕ' => 'h',
        'ₖ' => 'k',
        'ₗ' => 'l',
        'ₘ' => 'm',
        'ₙ' => 'n',
        'ₚ' => 'p',
        'ₛ' => 's',
        'ₜ' => 't',
    ];

    /**
     * HTML listo para MultiCell, o null si el texto se imprime igual que hoy.
     */
    public static function htmlSiHaceFalta(string $texto): ?string
    {
        if ($texto === '' || ! self::contiene($texto)) {
            return null;
        }

        $decodificado = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $partes = preg_split(
            '/(<\s*sup\s*>.*?<\s*\/\s*sup\s*>|<\s*sub\s*>.*?<\s*\/\s*sub\s*>)/is',
            $decodificado,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if (! is_array($partes)) {
            return null;
        }

        $html = '';
        foreach ($partes as $parte) {
            if (preg_match('/^<\s*(sup|sub)\s*>(.*?)<\s*\/\s*\1\s*>$/is', $parte, $m) === 1) {
                $interno = htmlspecialchars(self::aBase((string) $m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $html .= '<'.$m[1].'>'.$interno.'</'.$m[1].'>';

                continue;
            }

            $html .= self::escaparPlano($parte);
        }

        return $html;
    }

    /**
     * Texto para calcular el alto de la celda. Sin superíndices devuelve el original.
     */
    public static function paraMedir(string $texto): string
    {
        if (! self::contiene($texto)) {
            return $texto;
        }

        $decodificado = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $sinEtiquetas = preg_replace('/<\s*\/?\s*(?:sup|sub)\s*>/i', '', $decodificado) ?? $decodificado;

        return self::aBase($sinEtiquetas);
    }

    private static function contiene(string $texto): bool
    {
        if (preg_match('/<\s*\/?\s*(?:sup|sub)\b/i', $texto) === 1) {
            return true;
        }

        $decodificado = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return self::tieneIndice($decodificado);
    }

    private static function tieneIndice(string $texto): bool
    {
        if ($texto === '') {
            return false;
        }

        $clase = preg_quote(implode('', array_keys(self::SUPER + self::SUB)), '/');

        return preg_match('/['.$clase.']/u', $texto) === 1;
    }

    private static function escaparPlano(string $texto): string
    {
        $html = htmlspecialchars($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = self::envolver(self::SUPER, 'sup', $html);
        $html = self::envolver(self::SUB, 'sub', $html);
        $html = preg_replace_callback(
            '/ {2,}/',
            static fn (array $m): string => str_repeat('&nbsp;', strlen($m[0])),
            $html
        ) ?? $html;

        return str_replace("\n", '<br>', $html);
    }

    /**
     * @param  array<string, string>  $mapa
     */
    private static function envolver(array $mapa, string $etiqueta, string $html): string
    {
        $clase = preg_quote(implode('', array_keys($mapa)), '/');
        $reemplazo = preg_replace_callback(
            '/['.$clase.']+/u',
            static function (array $m) use ($mapa, $etiqueta): string {
                $base = '';
                $chars = preg_split('//u', $m[0], -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach ($chars as $char) {
                    $base .= $mapa[$char] ?? $char;
                }

                return '<'.$etiqueta.'>'.$base.'</'.$etiqueta.'>';
            },
            $html
        );

        return $reemplazo ?? $html;
    }

    private static function aBase(string $texto): string
    {
        return strtr($texto, self::SUPER + self::SUB);
    }
}
