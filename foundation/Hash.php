<?php
namespace Briko\Foundation;

class Hash
{
    public static function make(string $value, int $rounds = 12): string
    {
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => $rounds]);
    }

    public static function check(string $value, string $hashed): bool
    {
        if ($hashed === '') return false;
        return password_verify($value, $hashed);
    }

    public static function needsRehash(string $hashed, int $rounds = 12): bool
    {
        return password_needs_rehash($hashed, PASSWORD_BCRYPT, ['cost' => $rounds]);
    }
}
