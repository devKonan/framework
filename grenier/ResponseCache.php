<?php
namespace Briko\grenier;

class ResponseCache
{
    private static function dir(): string
    {
        $dir = base_path('storage/cache');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function filePath(string $uri): string
    {
        return self::dir() . '/' . md5($uri) . '.json';
    }

    public static function set(string $uri, mixed $response, int $ttl = 300): void
    {
        if (!is_array($response) && !is_string($response)) return;

        $data = [
            'uri'        => $uri,
            'response'   => $response,
            'cached_at'  => date('Y-m-d H:i:s'),
            'expires_at' => time() + $ttl,
        ];
        file_put_contents(self::filePath($uri), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function get(string $uri): mixed
    {
        $path = self::filePath($uri);
        if (!file_exists($path)) return null;

        $data = json_decode(file_get_contents($path), true);
        if (!$data) return null;

        if (time() > ($data['expires_at'] ?? 0)) {
            unlink($path);
            return null;
        }

        return $data['response'] ?? null;
    }

    public static function has(string $uri): bool
    {
        return self::get($uri) !== null;
    }

    public static function cachedAt(string $uri): ?string
    {
        $path = self::filePath($uri);
        if (!file_exists($path)) return null;
        $data = json_decode(file_get_contents($path), true);
        return $data['cached_at'] ?? null;
    }

    public static function forget(string $uri): void
    {
        $path = self::filePath($uri);
        if (file_exists($path)) unlink($path);
    }

    public static function flush(): void
    {
        foreach (glob(self::dir() . '/*.json') as $file) {
            unlink($file);
        }
    }

    public static function stats(): array
    {
        $files = glob(self::dir() . '/*.json') ?: [];
        return ['entries' => count($files), 'size_kb' => round(array_sum(array_map('filesize', $files)) / 1024, 2)];
    }
}
