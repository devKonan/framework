<?php

use Briko\Foundation\Env;
use Briko\Foundation\Logger;
use Briko\Database\DB;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('db')) {
    function db(string $table): \Briko\Database\QueryBuilder
    {
        return DB::table($table);
    }
}

if (!function_exists('sms')) {
    function sms(string|array $to): \Briko\Sms\SmsMessage
    {
        return \Briko\Sms\SMS::to($to);
    }
}

if (!function_exists('mail_to')) {
    function mail_to(string|array $address): \Briko\Mail\MailMessage
    {
        return \Briko\Mail\Mail::to($address);
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

if (!function_exists('view')) {
    function view(string $template, array $data = []): string
    {
        $file = base_path('app/views/' . str_replace('.', '/', $template) . '.php');

        if (!file_exists($file)) {
            throw new \RuntimeException("Vue introuvable : {$file}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}

// ─── Hash ─────────────────────────────────────────────────────────────────────

if (!function_exists('bcrypt')) {
    function bcrypt(string $value, int $rounds = 12): string
    {
        return \Briko\Foundation\Hash::make($value, $rounds);
    }
}

// ─── Validation ───────────────────────────────────────────────────────────────

if (!function_exists('validate')) {
    function validate(array $data, array $rules): \Briko\Foundation\Validator
    {
        return \Briko\Foundation\Validator::make($data, $rules);
    }
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \Briko\Http\Middleware\CsrfMiddleware::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

// ─── Auth ─────────────────────────────────────────────────────────────────────

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return \Briko\Foundation\Auth::user();
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return \Briko\Foundation\Auth::check();
    }
}
