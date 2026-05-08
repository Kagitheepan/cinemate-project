<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    $envFile = dirname(__DIR__).'/.env';
    $testEnvFile = dirname(__DIR__).'/.env.test';

    if (is_file($envFile)) {
        (new Dotenv())->bootEnv($envFile);
    } elseif (is_file($testEnvFile)) {
        (new Dotenv())->load($testEnvFile);
        $_SERVER['APP_ENV'] ??= 'test';
        $_SERVER['APP_DEBUG'] ??= '0';
    }
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}
