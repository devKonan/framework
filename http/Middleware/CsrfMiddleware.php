<?php
namespace Briko\Http\Middleware;

use Briko\Http\Request;
use Briko\Http\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    private const SESSION_KEY  = '_briko_csrf_token';

    public function handle(Request $request, callable $next): mixed
    {
        if (!in_array($request->method, self::SAFE_METHODS, true)) {
            if (!$this->tokenValid($request)) {
                Response::json(['error' => 'CSRF token invalide ou manquant.'], 419);
                exit;
            }
        }

        return $next($request);
    }

    private function tokenValid(Request $request): bool
    {
        $expected = static::token();
        $provided = $request->input('_token')
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        if (!$provided) return false;

        return hash_equals($expected, (string) $provided);
    }

    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function regenerate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }
}
