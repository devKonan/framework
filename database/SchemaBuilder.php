<?php
namespace Briko\Database;

class SchemaBuilder
{
    /** @var ColumnDefinition[] */
    private array $columns = [];
    private array $indexes = [];
    private bool  $isAlter = false;

    private function __construct(private readonly string $table, bool $alter = false)
    {
        $this->isAlter = $alter;
    }

    // ─── Static entry points ─────────────────────────────────────────────────

    public static function create(string $table, callable $fn): void
    {
        $builder = new static($table, false);
        $fn($builder);
        $builder->executeCreate();
    }

    public static function table(string $table, callable $fn): void
    {
        $builder = new static($table, true);
        $fn($builder);
        $builder->executeAlter();
    }

    public static function drop(string $table): void
    {
        Connection::get()->exec("DROP TABLE IF EXISTS `{$table}`");
    }

    public static function hasTable(string $table): bool
    {
        try {
            $stmt = Connection::get()->prepare(
                "SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?"
            );
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            // SQLite fallback
            $stmt = Connection::get()->prepare(
                "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=?"
            );
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        }
    }

    // ─── Column builders ─────────────────────────────────────────────────────

    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->col("`{$name}` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY");
    }

    public function bigIncrements(string $name = 'id'): ColumnDefinition
    {
        return $this->col("`{$name}` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY");
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->col("`{$name}` VARCHAR({$length}) NOT NULL DEFAULT ''");
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` TEXT");
    }

    public function longText(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` LONGTEXT");
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` INT NOT NULL DEFAULT 0");
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` BIGINT NOT NULL DEFAULT 0");
    }

    public function tinyInteger(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` TINYINT NOT NULL DEFAULT 0");
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->col("`{$name}` DECIMAL({$precision},{$scale}) NOT NULL DEFAULT 0");
    }

    public function float(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` FLOAT NOT NULL DEFAULT 0");
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` DATE NULL DEFAULT NULL");
    }

    public function datetime(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` DATETIME NULL DEFAULT NULL");
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` TIMESTAMP NULL DEFAULT NULL");
    }

    public function timestamps(): void
    {
        $this->col('`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->col('`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` JSON NULL");
    }

    public function foreignId(string $name): ColumnDefinition
    {
        return $this->col("`{$name}` INT UNSIGNED NOT NULL DEFAULT 0");
    }

    // ─── Index helpers ────────────────────────────────────────────────────────

    public function index(string ...$columns): void
    {
        $cols = '`' . implode('`, `', $columns) . '`';
        $this->indexes[] = "INDEX ({$cols})";
    }

    public function uniqueIndex(string ...$columns): void
    {
        $cols = '`' . implode('`, `', $columns) . '`';
        $this->indexes[] = "UNIQUE KEY ({$cols})";
    }

    // ─── Execution ────────────────────────────────────────────────────────────

    private function executeCreate(): void
    {
        $parts = array_map(fn (ColumnDefinition $c) => $c->compile(), $this->columns);
        $all   = array_merge($parts, $this->indexes);

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (\n  "
            . implode(",\n  ", $all)
            . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        Connection::get()->exec($sql);
    }

    private function executeAlter(): void
    {
        foreach ($this->columns as $col) {
            Connection::get()->exec(
                "ALTER TABLE `{$this->table}` ADD COLUMN " . $col->compile()
            );
        }

        foreach ($this->indexes as $idx) {
            Connection::get()->exec(
                "ALTER TABLE `{$this->table}` ADD {$idx}"
            );
        }
    }

    private function col(string $sql): ColumnDefinition
    {
        $def = new ColumnDefinition($sql);
        $this->columns[] = $def;
        return $def;
    }
}
