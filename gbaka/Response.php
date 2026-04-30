<?php
namespace Briko\gbaka;

class Response
{
    private static bool   $compressionEnabled = false;
    private static ?Request $currentRequest   = null;

    public static function enableCompression(Request $request): void
    {
        self::$compressionEnabled = true;
        self::$currentRequest     = $request;
    }

    public static function send($content, int $status = 200): void
    {
        http_response_code($status);

        if (is_array($content) || is_object($content)) {
            header('Content-Type: application/json; charset=utf-8');
            $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            static::emit($json);
            return;
        }

        static::emit((string) $content);
    }

    public static function json(array $data, int $status = 200): void
    {
        static::send($data, $status);
    }

    public static function html(string $html, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    public static function notFound(string $message = 'Non trouvé'): void
    {
        static::json(['error' => $message], 404);
    }

    public static function error(string $message = 'Erreur serveur', int $status = 500): void
    {
        static::json(['error' => $message], $status);
    }

    private static function emit(string $body): void
    {
        $req = self::$currentRequest;

        if (self::$compressionEnabled && $req && $req->acceptsGzip()) {
            $compressed = gzencode($body, 6);
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
            header('X-Original-Size: ' . strlen($body));
            header('X-Compressed-Size: ' . strlen($compressed));
            echo $compressed;
        } else {
            echo $body;
        }

        self::$compressionEnabled = false;
        self::$currentRequest     = null;
    }
}
