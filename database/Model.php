<?php
namespace Briko\Database;

abstract class Model
{
    protected static string $table      = '';
    protected static string $primaryKey = 'id';

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public static function all(): array
    {
        return DB::table(static::$table)->get();
    }

    public static function find(int|string $id): ?array
    {
        return DB::table(static::$table)->find($id);
    }

    public static function findOrFail(int|string $id): array
    {
        return DB::table(static::$table)->findOrFail($id);
    }

    public static function where(string $col, mixed $val): QueryBuilder
    {
        return DB::table(static::$table)->where($col, $val);
    }

    public static function create(array $data): int|string
    {
        return DB::table(static::$table)->insertGetId($data);
    }

    public static function update(int|string $id, array $data): int
    {
        return DB::table(static::$table)
            ->where(static::$primaryKey, $id)
            ->update($data);
    }

    public static function delete(int|string $id): int
    {
        return DB::table(static::$table)
            ->where(static::$primaryKey, $id)
            ->delete();
    }

    public static function count(): int
    {
        return DB::table(static::$table)->count();
    }

    public static function paginate(int $perPage = 15, int $page = 1): array
    {
        return DB::table(static::$table)->paginate($perPage, $page);
    }

    public static function latest(string $column = 'created_at'): QueryBuilder
    {
        return DB::table(static::$table)->latest($column);
    }

    public static function query(): QueryBuilder
    {
        return DB::table(static::$table);
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * Un enregistrement de cette table possède plusieurs lignes dans $relatedTable.
     * Ex: User::hasMany('posts', 'user_id', $userId)
     */
    public static function hasMany(
        string     $relatedTable,
        string     $foreignKey,
        int|string $parentId
    ): array {
        return DB::table($relatedTable)->where($foreignKey, $parentId)->get();
    }

    /**
     * Un enregistrement de cette table possède au plus une ligne dans $relatedTable.
     * Ex: User::hasOne('profiles', 'user_id', $userId)
     */
    public static function hasOne(
        string     $relatedTable,
        string     $foreignKey,
        int|string $parentId
    ): ?array {
        return DB::table($relatedTable)->where($foreignKey, $parentId)->first();
    }

    /**
     * Un enregistrement appartient à une ligne de $relatedTable.
     * Ex: Post::belongsTo('users', $post['user_id'])
     */
    public static function belongsTo(
        string     $relatedTable,
        int|string $foreignKeyValue,
        string     $ownerKey = 'id'
    ): ?array {
        return DB::table($relatedTable)->where($ownerKey, $foreignKeyValue)->first();
    }

    /**
     * Relation Many-to-Many via table pivot.
     * Ex: User::belongsToMany('roles', 'role_user', 'user_id', 'role_id', $userId)
     */
    public static function belongsToMany(
        string     $relatedTable,
        string     $pivotTable,
        string     $foreignKey,
        string     $relatedKey,
        int|string $parentId
    ): array {
        $pivotRows = DB::table($pivotTable)->where($foreignKey, $parentId)->get();
        if (empty($pivotRows)) return [];

        $relatedIds = array_column($pivotRows, $relatedKey);
        $results    = [];

        foreach ($relatedIds as $rid) {
            $row = DB::table($relatedTable)->find($rid);
            if ($row !== null) $results[] = $row;
        }

        return $results;
    }
}
