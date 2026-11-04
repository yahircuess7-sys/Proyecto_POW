<?php

/**
 * Entrypoint Serverless de PuntoÁgil para Vercel (vercel-php runtime).
 * Encamina todas las solicitudes HTTP dinámicas hacia el Front Controller de Laravel.
 */

$_ENV['VERCEL'] = $_ENV['VERCEL'] ?? '1';
$_SERVER['VERCEL'] = $_SERVER['VERCEL'] ?? '1';

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

require __DIR__ . '/../public/index.php';
