<?php
namespace Briko\Foundation;

use Briko\Database\DB;

class Validator
{
    private array $errors = [];

    private function __construct(
        private readonly array $data,
        private readonly array $rules
    ) {}

    public static function make(array $data, array $rules): static
    {
        $v = new static($data, $rules);
        $v->run();
        return $v;
    }

    public function passes(): bool  { return empty($this->errors); }
    public function fails(): bool   { return !empty($this->errors); }
    public function errors(): array { return $this->errors; }

    public function firstError(string $field = ''): ?string
    {
        if ($field) return $this->errors[$field][0] ?? null;
        foreach ($this->errors as $messages) {
            return $messages[0];
        }
        return null;
    }

    // ─── Engine ──────────────────────────────────────────────────────────────

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : (array) $ruleSet;
            $value = $this->data[$field] ?? null;
            foreach ($rules as $rule) {
                $this->apply($field, $value, (string) $rule);
            }
        }
    }

    private function apply(string $field, mixed $value, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
        $empty = $value === null || $value === '';

        switch ($name) {
            case 'required':
                if ($empty) $this->err($field, "Le champ {$field} est obligatoire.");
                break;

            case 'email':
                if (!$empty && !filter_var($value, FILTER_VALIDATE_EMAIL))
                    $this->err($field, "Le champ {$field} doit être une adresse email valide.");
                break;

            case 'min':
                if (!$empty) {
                    if (is_numeric($value) && (float)$value < (float)$param)
                        $this->err($field, "Le champ {$field} doit être ≥ {$param}.");
                    elseif (is_string($value) && mb_strlen($value) < (int)$param)
                        $this->err($field, "Le champ {$field} doit contenir au moins {$param} caractères.");
                }
                break;

            case 'max':
                if (!$empty) {
                    if (is_numeric($value) && (float)$value > (float)$param)
                        $this->err($field, "Le champ {$field} doit être ≤ {$param}.");
                    elseif (is_string($value) && mb_strlen($value) > (int)$param)
                        $this->err($field, "Le champ {$field} ne peut pas dépasser {$param} caractères.");
                }
                break;

            case 'numeric':
                if (!$empty && !is_numeric($value))
                    $this->err($field, "Le champ {$field} doit être un nombre.");
                break;

            case 'integer':
                if (!$empty && filter_var($value, FILTER_VALIDATE_INT) === false)
                    $this->err($field, "Le champ {$field} doit être un entier.");
                break;

            case 'string':
                if (!$empty && !is_string($value))
                    $this->err($field, "Le champ {$field} doit être une chaîne de caractères.");
                break;

            case 'boolean':
                if (!$empty && !in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true))
                    $this->err($field, "Le champ {$field} doit être vrai ou faux.");
                break;

            case 'url':
                if (!$empty && !filter_var($value, FILTER_VALIDATE_URL))
                    $this->err($field, "Le champ {$field} doit être une URL valide.");
                break;

            case 'confirmed':
                $confirmKey = $field . '_confirmation';
                if (($this->data[$confirmKey] ?? null) !== $value)
                    $this->err($field, "La confirmation du champ {$field} ne correspond pas.");
                break;

            case 'in':
                $allowed = explode(',', $param ?? '');
                if (!$empty && !in_array((string)$value, $allowed, true))
                    $this->err($field, "La valeur du champ {$field} n'est pas autorisée.");
                break;

            case 'not_in':
                $forbidden = explode(',', $param ?? '');
                if (!$empty && in_array((string)$value, $forbidden, true))
                    $this->err($field, "La valeur du champ {$field} n'est pas autorisée.");
                break;

            case 'regex':
                if (!$empty && !preg_match($param ?? '//', (string)$value))
                    $this->err($field, "Le champ {$field} a un format invalide.");
                break;

            case 'unique':
                // unique:table  ou  unique:table,column
                [$table, $col] = array_pad(explode(',', $param ?? '', 2), 2, $field);
                if (!$empty && DB::table($table)->where($col, $value)->exists())
                    $this->err($field, "La valeur du champ {$field} est déjà utilisée.");
                break;

            case 'exists':
                // exists:table  ou  exists:table,column
                [$table, $col] = array_pad(explode(',', $param ?? '', 2), 2, $field);
                if (!$empty && !DB::table($table)->where($col, $value)->exists())
                    $this->err($field, "La valeur du champ {$field} n'existe pas.");
                break;
        }
    }

    private function err(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
