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
    | chrome_path juga menerima PUPPETEER_EXECUTABLE_PATH (nama standar
    | Puppeteer). Di Railway variabel itu diisi otomatis oleh start command
    | di railway.json dengan path Chromium dari Nix.
    |
    */

    'node_binary' => env('BROWSERSHOT_NODE_BINARY'),

    'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH') ?: env('PUPPETEER_EXECUTABLE_PATH'),

];
