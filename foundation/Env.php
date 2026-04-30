<?php
namespace Briko\Foundation;

class Env
{
    private static array $vars = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            if (preg_match('/^"(.*)"$/s', $value, $m) || preg_match("/^'(.*)'$/s", $value, $m)) {
                $value = $m[1];
            }

            self::$vars[$key] = $value;
            $_ENV[$key]       = $value;
            putenv("$key=$value");
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$vars[$key] ?? $_ENV[$key] ?? (getenv($key) !== false ? getenv($key) : $default);
    }
}
