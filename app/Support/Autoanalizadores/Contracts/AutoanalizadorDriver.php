<?php

namespace App\Support\Autoanalizadores\Contracts;

/**
 * Parser de un modelo de aparato. Devuelve valores crudos (numéricos ya limpios)
 * indexados por código idAnalizador; el perfil del lab aplica formato después.
 */
interface AutoanalizadorDriver
{
    /**
     * @return array<string, string>|null  null = protocolo no encontrado en el archivo
     */
    public function buscarPorProtocolo(string $rutaCsv, string $nombreProtocolo): ?array;
}
