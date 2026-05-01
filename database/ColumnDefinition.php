<?php
namespace Briko\Database;

class ColumnDefinition
{
    private const UNSET = '__UNSET__';

    private bool  $isNullable = false;
    private mixed $defaultVal = self::UNSET;
    private bool  $isUnique   = false;

    public function __construct(private string $base) {}

    public function nullable(): static
    {
        $this->isNullable = true;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->defaultVal = $value;
        return $this;
    }

    public function unique(): static
    {
        $this->isUnique = true;
        return $this;
    }

    public function compile(): string
    {
        $sql = $this->base;

        if ($this->isNullable) {
            $sql = str_replace('NOT NULL', 'NULL', $sql);
            // Remove any inline DEFAULT '' / DEFAULT 0 when nullable + no custom default
            if ($this->defaultVal === self::UNSET) {
                $sql = preg_replace("/\\s+DEFAULT\\s+''/", '', $sql);
                $sql = preg_replace('/\s+DEFAULT\s+0\b/', '', $sql);
            }
        }

        if ($this->defaultVal !== self::UNSET) {
            $sql = preg_replace('/\s+DEFAULT\s+\S+/', '', $sql);
            if ($this->defaultVal === null) {
                $sql .= ' DEFAULT NULL';
            } elseif (is_bool($this->defaultVal)) {
                $sql .= ' DEFAULT ' . ($this->defaultVal ? '1' : '0');
            } elseif (is_int($this->defaultVal) || is_float($this->defaultVal)) {
                $sql .= ' DEFAULT ' . $this->defaultVal;
            } else {
                $sql .= " DEFAULT '" . addslashes((string) $this->defaultVal) . "'";
            }
        }

        if ($this->isUnique) {
            $sql .= ' UNIQUE';
        }

        return $sql;
    }
}
