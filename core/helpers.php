<?php

use Briko\core\Env;
use Briko\core\Logger;
use Briko\grenier\DB;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('db')) {
    function db(string $table): \Briko\grenier\QueryBuilder
    {
        return DB::table($table);
    }
}

if (!function_exists('sms')) {
    function sms(string|array $to): \Briko\tamtam\SmsMessage
    {
        return \Briko\tamtam\SMS::to($to);
    }
}

if (!function_exists('logger')) {
    function logger(string $message, array $context = [], string $level = 'info'): void
    {
        Logger::{$level}($message, $context);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $base;
    }
}
