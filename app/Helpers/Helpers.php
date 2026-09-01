<?php

use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

if (! function_exists('encode_id')) {
    /**
     * Codifica un ID numerico para exponerlo en rutas y formularios.
     */
    function encode_id(int $id): string
    {
        return Hashids::encode($id);
    }
}

if (! function_exists('decode_id')) {
    /**
     * Decodifica un hash generado por encode_id().
     *
     * Devuelve null cuando el hash es nulo, vacio o no corresponde al salt
     * configurado, para que quien lo consuma decida si aborta con un 404.
     */
    function decode_id(?string $hash): ?int
    {
        if (blank($hash)) {
            return null;
        }

        $decodificado = Hashids::decode($hash);

        return isset($decodificado[0]) ? (int) $decodificado[0] : null;
    }
}

if (! function_exists('decode_id_or_fail')) {
    /**
     * Igual que decode_id(), pero aborta con 404 cuando el hash es invalido.
     *
     * Pensado para rutas: evita repetir la comprobacion en cada controlador.
     */
    function decode_id_or_fail(?string $hash): int
    {
        return decode_id($hash) ?? abort(404);
    }
}

if (! function_exists('normalizar_texto')) {
    /**
     * Reduce un texto a mayusculas sin tildes ni signos, con los espacios
     * colapsados.
     *
     * Sirve para cruzar nombres que llegan escritos de formas distintas en los
     * padrones oficiales: "Educación Secundaria: Especialidad Idioma Inglés" y
     * "EDUCACION SECUNDARIA - ESPECIALIDAD IDIOMA INGLES" caen en el mismo
     * valor.
     */
    function normalizar_texto(?string $texto): string
    {
        if (blank($texto)) {
            return '';
        }

        $sinTildes = Str::ascii(mb_strtoupper(trim($texto)));
        $soloAlfanumericos = preg_replace('/[^A-Z0-9]+/', ' ', $sinTildes) ?? '';

        return trim(preg_replace('/\s+/', ' ', $soloAlfanumericos) ?? '');
    }
}
