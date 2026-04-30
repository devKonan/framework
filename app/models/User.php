<?php
namespace App\Models;

use Briko\Database\DB;

class User
{
    protected static string $table = 'users';

    public static function all(): array
    {
        return DB::table(static::$table)->get();
    }

    public static function find(int|string $id): ?array
    {
        return DB::table(static::$table)->find($id);
    }

    public static function where(string $col, mixed $val): array
    {
        return DB::table(static::$table)->where($col, $val)->get();
    }

    public static function create(array $data): int|string
    {
        return DB::table(static::$table)->insertGetId($data);
    }

    public static function update(int|string $id, array $data): int
    {
        return DB::table(static::$table)->where('id', $id)->update($data);
    }

    public static function delete(int|string $id): int
    {
        return DB::table(static::$table)->where('id', $id)->delete();
    }
}
