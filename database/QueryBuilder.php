<?php
namespace Briko\Database;

use PDO;

class QueryBuilder
{
    private string  $table;
    private array   $wheres   = [];
    private array   $bindings = [];
    private array   $orWheres = [];
    private array   $orBindings = [];
    private ?int    $limitVal = null;
    private ?int    $offsetVal = null;
    private ?string $orderCol = null;
    private string  $orderDir = 'ASC';
    private array   $selects  = ['*'];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function select(string ...$columns): static
    {
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, mixed $value, string $operator = '='): static
    {
        $this->wheres[]   = "$column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $value, string $operator = '='): static
    {
        $this->orWheres[]    = "$column $operator ?";
        $this->orBindings[]  = $value;
        return $this;
    }

    public function limit(int $n): static
    {
        $this->limitVal = $n;
        return $this;
    }

    public function offset(int $n): static
    {
        $this->offsetVal = $n;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderCol = $column;
        $this->orderDir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    // ─── Lecture ─────────────────────────────────────────────────────────────

    public function get(): array
    {
        $cols = implode(', ', $this->selects);
        $sql  = "SELECT $cols FROM {$this->table}" . $this->buildWhere();
        if ($this->orderCol) $sql .= " ORDER BY {$this->orderCol} {$this->orderDir}";
        if ($this->limitVal !== null) $sql .= " LIMIT {$this->limitVal}";
        if ($this->offsetVal !== null) $sql .= " OFFSET {$this->offsetVal}";

        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($this->allBindings());
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $result = $this->limit(1)->get();
        return $result[0] ?? null;
    }

    public function find(int|string $id): ?array
    {
        return $this->where('id', $id)->first();
    }

    public function findOrFail(int|string $id): array
    {
        $row = $this->find($id);
        if ($row === null) {
            throw new \RuntimeException("Enregistrement {$id} non trouvé dans {$this->table}");
        }
        return $row;
    }

    public function count(): int
    {
        $sql  = "SELECT COUNT(*) as total FROM {$this->table}" . $this->buildWhere();
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($this->allBindings());
        return (int) $stmt->fetch()['total'];
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function pluck(string $column): array
    {
        $rows = $this->select($column)->get();
        return array_column($rows, $column);
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total   = $this->count();
        $items   = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();
        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    // ─── Écriture ─────────────────────────────────────────────────────────────

    public function insert(array $data): bool
    {
        $cols         = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql          = "INSERT INTO {$this->table} ($cols) VALUES ($placeholders)";
        $stmt         = Connection::get()->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public function insertGetId(array $data): int|string
    {
        $this->insert($data);
        return Connection::get()->lastInsertId();
    }

    public function update(array $data): int
    {
        $sets = implode(', ', array_map(fn ($col) => "$col = ?", array_keys($data)));
        $sql  = "UPDATE {$this->table} SET $sets" . $this->buildWhere();
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute([...array_values($data), ...$this->allBindings()]);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql  = "DELETE FROM {$this->table}" . $this->buildWhere();
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($this->allBindings());
        return $stmt->rowCount();
    }

    // ─── SQL brut ─────────────────────────────────────────────────────────────

    public function raw(string $sql, array $bindings = []): array
    {
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    public function rawExec(string $sql, array $bindings = []): int
    {
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    // ─── Helpers privés ────────────────────────────────────────────────────────

    private function buildWhere(): string
    {
        $parts = [];
        if (!empty($this->wheres)) {
            $parts[] = implode(' AND ', $this->wheres);
        }
        if (!empty($this->orWheres)) {
            $parts[] = implode(' OR ', $this->orWheres);
        }
        return empty($parts) ? '' : ' WHERE ' . implode(' OR ', $parts);
    }

    private function allBindings(): array
    {
        return [...$this->bindings, ...$this->orBindings];
    }
}
