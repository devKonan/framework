<?php
namespace Briko\Database;

use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect();
        }
        return self::$pdo;
    }

    private static function connect(): PDO
    {
        $driver = env('DB_DRIVER', 'mysql');
        $host   = env('DB_HOST', '127.0.0.1');
        $port   = env('DB_PORT', '3306');
        $name   = env('DB_NAME', 'brikocode');
        $user   = env('DB_USER', 'root');
        $pass   = env('DB_PASS', '');

        $dsn = match ($driver) {
            'sqlite' => 'sqlite:' . base_path(env('DB_PATH', 'database/db.sqlite')),
            'pgsql'  => "pgsql:host=$host;port=$port;dbname=$name",
            'mysql'  => "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
            default  => throw new RuntimeException("Driver DB non supporté : $driver"),
        };

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Connexion DB échouée : ' . $e->getMessage());
        }
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
