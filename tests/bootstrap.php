<?php

/*
 * PHPUnit bootstrap.
 *
 * In de Docker-omgeving laadt docker-compose de app-config via `env_file: .env`
 * als échte OS-environmentvariabelen. Die belanden in $_SERVER, en Laravel's
 * Env-reader raadpleegt $_SERVER vóór $_ENV. PHPUnit's <env>-entries in
 * phpunit.xml zetten echter alleen $_ENV/putenv (zelfs met force="true"), dus
 * zonder deze bootstrap zouden de tests in de `local`-omgeving draaien
 * (CSRF-middleware actief → 419, verkeerde cache/queue/session-drivers).
 *
 * We forceren de test-omgevingswaarden hier ook in $_SERVER, zodat ze winnen
 * ongeacht wat een lokale `.env` injecteert of hoe de tests gestart worden.
 * De waarden spiegelen de <env>-entries in phpunit.xml.
 */
$testEnvironment = [
    'APP_ENV' => 'testing',
    // Vaste, afgeleide testsleutel. Geen geheim: hij staat los van elke echte
    // omgeving en zorgt dat de suite niet afhangt van een gevulde .env. Zonder
    // deze regel lekt een lege APP_KEY uit docker-compose binnen en falen alle
    // tests die de encrypter raken op MissingAppKeyException.
    'APP_KEY' => 'base64:'.base64_encode(hash('sha256', 'mototrax-testing', true)),
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_CONNECTION' => 'null',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
];

foreach ($testEnvironment as $key => $value) {
    $_SERVER[$key] = $value;
    $_ENV[$key] = $value;
    putenv("{$key}={$value}");
}

require __DIR__.'/../vendor/autoload.php';
