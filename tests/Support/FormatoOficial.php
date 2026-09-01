<?php

namespace Tests\Support;

/**
 * Cabeceras y filas de ejemplo del formato oficial de inscripcion, para no
 * repetir las treinta y cuatro columnas en cada prueba.
 */
class FormatoOficial
{
    /**
     * @return list<string>
     */
    public static function cabecera(): array
    {
        return [
            'CODIGO_TIPO_DOCUMENTO',
            'NUMERO_DOCUMENTO',
            'CODIGO_SOLO_UN_APELLIDO',
            'PRIMER_APELLIDO',
            'SEGUNDO_APELLIDO',
            'NOMBRES',
            'ESTADO_CIVIL',
            'APELLIDO_CASADA',
            'SEXO',
            'CODIGO_NACIMIENTO_PAIS',
            'CODIGO_NACIONALIDAD',
            'CODIGO_NACIMIENTO_UBIGEO',
            'FECHA_NACIMIENTO',
            'CONDICION_DISCAPACIDAD',
            'TIPO_DISCAPACIDAD',
            'CELULAR',
            'TELEFONO',
            'CORREO_ELECTRONICO',
            'CODIGO_UBIGEO_DIRECCION',
            'DIRECCION',
            'GRADUACION',
            'CODIGO_PAIS_COLEGIO',
            'CODIGO_COLEGIO',
            'NOMBRE_COLEGIO',
            'TIPO_COLEGIO',
            'VECES_POST_UNU',
            'VECES_POST_OTROS',
            'LENGUA_MATERNA',
            'IDENTIDAD_ETNICA',
            'LENGUA_NATIVA',
            'LENGUA_EXTRANJERA',
            'CODIGO_LUGAR_INSCRIPCION',
            'CODIGO_CARRERA',
            'OBSERVACION',
        ];
    }

    /**
     * Una fila valida a la que se le pueden pisar columnas sueltas.
     *
     * @param  array<string, string>  $cambios
     * @return list<string>
     */
    public static function fila(array $cambios = []): array
    {
        $valores = [
            'CODIGO_TIPO_DOCUMENTO' => '1',
            'NUMERO_DOCUMENTO' => '62035505',
            'CODIGO_SOLO_UN_APELLIDO' => '0',
            'PRIMER_APELLIDO' => 'SHUPINGAHUA',
            'SEGUNDO_APELLIDO' => 'REATEGUI',
            'NOMBRES' => 'YOJANA VALENTINA',
            'ESTADO_CIVIL' => 'SOLTERO(A)',
            'APELLIDO_CASADA' => '',
            'SEXO' => 'F',
            'CODIGO_NACIMIENTO_PAIS' => '1',
            'CODIGO_NACIONALIDAD' => '1',
            'CODIGO_NACIMIENTO_UBIGEO' => '250101',
            'FECHA_NACIMIENTO' => '2/2/2009',
            'CONDICION_DISCAPACIDAD' => '0',
            'TIPO_DISCAPACIDAD' => '',
            'CELULAR' => '985889393',
            'TELEFONO' => '',
            'CORREO_ELECTRONICO' => 'yojana@example.com',
            'CODIGO_UBIGEO_DIRECCION' => '250101',
            'DIRECCION' => 'JR. LOS ROSALES 123',
            'GRADUACION' => '2025',
            'CODIGO_PAIS_COLEGIO' => '1',
            'CODIGO_COLEGIO' => '0238808',
            'NOMBRE_COLEGIO' => '64035 AGROPECUARIO',
            'TIPO_COLEGIO' => '1',
            'VECES_POST_UNU' => '1',
            'VECES_POST_OTROS' => '0',
            'LENGUA_MATERNA' => 'CASTELLANO',
            'IDENTIDAD_ETNICA' => '',
            'LENGUA_NATIVA' => '',
            'LENGUA_EXTRANJERA' => '',
            'CODIGO_LUGAR_INSCRIPCION' => '593',
            'CODIGO_CARRERA' => '2567',
            'OBSERVACION' => '',
        ];

        return array_values(array_merge($valores, $cambios));
    }

    /**
     * Hoja FORMATO completa: cabecera mas las filas indicadas.
     *
     * @param  list<list<string>>  $filas
     * @return list<list<string>>
     */
    public static function hoja(array $filas): array
    {
        return array_merge([self::cabecera()], $filas);
    }
}
