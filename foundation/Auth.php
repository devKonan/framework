<?php
namespace Briko\Foundation;

use Briko\Database\DB;

class Auth
{
    private const SESSION_KEY  = '_briko_auth_id';
    private const TOKEN_COLUMN = 'api_token';

    private static string $table      = 'users';
    private static string $primaryKey = 'id';

    // ─── Session auth ─────────────────────────────────────────────────────────

    public static function attempt(string $email, string $password): bool
    {
        self::startSession();

        $user = DB::table(self::$table)->where('email', $email)->first();
        if (!$user) return false;
        if (!Hash::check($password, $user['password'] ?? '')) return false;

        $_SESSION[self::SESSION_KEY] = $user[self::$primaryKey];
        return true;
    }

    public static function login(array $user): void
    {
        self::startSession();
        $_SESSION[self::SESSION_KEY] = $user[self::$primaryKey];
    }

    public static function logout(): void
    {
        self::startSession();
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function user(): ?array
    {
        self::startSession();
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if ($id === null) return null;

        return DB::table(self::$table)->find($id);
    }

    public static function id(): int|string|null
    {
        self::startSession();
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    // ─── Registration ─────────────────────────────────────────────────────────

    public static function register(array $data): int|string
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return DB::table(self::$table)->insertGetId($data);
    }

    // ─── Token-based (API) ────────────────────────────────────────────────────

    public static function generateToken(int|string $userId): string
    {
        $plain  = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $plain);

        DB::table(self::$table)
            ->where(self::$primaryKey, $userId)
            ->update([self::TOKEN_COLUMN => $hashed]);

        return $plain;
    }

    public static function userByToken(string $plainToken): ?array
    {
        $hashed = hash('sha256', $plainToken);
        return DB::table(self::$table)->where(self::TOKEN_COLUMN, $hashed)->first();
    }

    public static function viaToken(): bool
    {
        $bearer = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($bearer, 'Bearer ')) {
            $bearer = substr($bearer, 7);
        }
        if (!$bearer) return false;

        return self::userByToken($bearer) !== null;
    }

    public static function userFromRequest(): ?array
    {
        $bearer = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($bearer, 'Bearer ')) {
            return self::userByToken(substr($bearer, 7));
        }
        return self::user();
    }

    // ─── Config ───────────────────────────────────────────────────────────────

    public static function useTable(string $table, string $primaryKey = 'id'): void
    {
        self::$table      = $table;
        self::$primaryKey = $primaryKey;
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
