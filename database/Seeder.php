<?php
namespace Briko\Database;

abstract class Seeder
{
    abstract public function run(): void;

    protected function call(string ...$seederClasses): void
    {
        foreach ($seederClasses as $class) {
            $dir  = base_path('database/seeders');
            $file = $dir . '/' . $this->shortName($class) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }

            if (!class_exists($class)) {
                throw new \RuntimeException("Seeder introuvable : {$class}");
            }

            echo "  → Calling " . $this->shortName($class) . "...\n";
            (new $class())->run();
        }
    }

    private function shortName(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }
}
