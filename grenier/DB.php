<?php
namespace Briko\grenier;

use PDO;

class DB
{
    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }

    public static function raw(string $sql, array $bindings = []): array
    {
        return (new QueryBuilder(''))->raw($sql, $bindings);
    }

    public static function exec(string $sql, array $bindings = []): int
    {
        return (new QueryBuilder(''))->rawExec($sql, $bindings);
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = Connection::get();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function lastInsertId(): string
    {
        return Connection::get()->lastInsertId();
    }
}
