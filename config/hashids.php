<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conexion por defecto
    |--------------------------------------------------------------------------
    |
    | Conexion que usan los helpers encode_id() y decode_id().
    |
    */

    'default' => 'main',

    /*
    |--------------------------------------------------------------------------
    | Conexiones
    |--------------------------------------------------------------------------
    |
    | El salt define los hashes generados: si cambia, todos los enlaces
    | emitidos antes dejan de resolver. No lo modifiques en produccion.
    |
    */

    'connections' => [

        'main' => [
            'salt' => env('HASHIDS_SALT') ?: env('APP_KEY', ''),
            'length' => (int) env('HASHIDS_LENGTH', 12),
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],

    ],

];
