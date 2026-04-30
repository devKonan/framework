<?php
namespace Briko\Foundation;

class Container
{
    protected array $bindings = [];

    public function set(string $key, $value): void
    {
        $this->bindings[$key] = $value;
    }

    public function get(string $key)
    {
        return $this->bindings[$key] ?? null;
    }
}
