<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Browsershot binary paths
    |--------------------------------------------------------------------------
    |
    | Isi lewat .env kalau node/npm/chrome tidak ada di PATH sistem (umum
    | terjadi di Windows/Laragon). Biarkan null untuk memakai default
    | Browsershot (mencari di PATH).
    |
    */

    'node_binary' => env('BROWSERSHOT_NODE_BINARY'),

    'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),

];
