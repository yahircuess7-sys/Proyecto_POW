<?php

/**
 * Entrypoint Serverless de PuntoÁgil para Vercel (vercel-php runtime).
 * Encamina todas las solicitudes HTTP dinámicas hacia el Front Controller de Laravel.
 */

$_ENV['VERCEL'] = $_ENV['VERCEL'] ?? '1';
$_SERVER['VERCEL'] = $_SERVER['VERCEL'] ?? '1';
putenv('VERCEL=1');

// Sincronizar variables de entorno inyectadas por Vercel en $_SERVER hacia $_ENV y putenv()
foreach ($_SERVER as $key => $val) {
    if (is_string($key) && (is_string($val) || is_numeric($val))) {
        if (! isset($_ENV[$key])) {
            $_ENV[$key] = (string) $val;
        }
        if (getenv($key) === false) {
            @putenv("{$key}={$val}");
        }
    }
}

$tmpStorage = '/tmp/storage';
foreach ([
    $tmpStorage . '/framework/views',
    $tmpStorage . '/framework/cache/data',
    $tmpStorage . '/framework/sessions',
    $tmpStorage . '/logs',
    $tmpStorage . '/app/public',
] as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// Respaldo seguro en caso de que el usuario aún no haya cargado APP_KEY en el panel de Vercel
if (empty(getenv('APP_KEY')) && empty($_ENV['APP_KEY']) && empty($_SERVER['APP_KEY'])) {
    $seed = getenv('VERCEL_PROJECT_ID') ?: (getenv('VERCEL_URL') ?: 'punto-agil-vercel-runtime-key');
    $fallbackKey = 'base64:' . base64_encode(hash('sha256', $seed, true));
    $_ENV['APP_KEY'] = $fallbackKey;
    $_SERVER['APP_KEY'] = $fallbackKey;
    putenv("APP_KEY={$fallbackKey}");
}

require __DIR__ . '/../public/index.php';
