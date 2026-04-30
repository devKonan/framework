<?php
namespace Briko\tamtam;

class OtpManager
{
    private static function dir(): string
    {
        $dir = base_path('storage/otp');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    private static function file(string $phone): string
    {
        return self::dir() . '/' . md5($phone) . '.json';
    }

    /**
     * Génère un code OTP, le stocke et retourne le code en clair.
     * L'envoi SMS est géré par SMS::otp().
     */
    public static function generate(string $phone, int $length = 6, int $ttlMinutes = 5): string
    {
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        $data = [
            'phone'      => $phone,
            'hash'       => password_hash($code, PASSWORD_BCRYPT),
            'expires_at' => time() + ($ttlMinutes * 60),
            'attempts'   => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents(self::file($phone), json_encode($data));
        return $code;
    }

    /**
     * Vérifie un code OTP. Retourne true si valide, false sinon.
     * Supprime le code après vérification réussie.
     * Bloque après 3 tentatives échouées.
     */
    public static function verify(string $phone, string $code): bool
    {
        $file = self::file($phone);
        if (!file_exists($file)) return false;

        $data = json_decode(file_get_contents($file), true);
        if (!$data) return false;

        if (time() > ($data['expires_at'] ?? 0)) {
            unlink($file);
            return false;
        }

        if (($data['attempts'] ?? 0) >= 3) {
            unlink($file);
            return false;
        }

        $data['attempts']++;
        file_put_contents($file, json_encode($data));

        if (password_verify($code, $data['hash'])) {
            unlink($file);
            return true;
        }

        return false;
    }

    public static function isPending(string $phone): bool
    {
        $file = self::file($phone);
        if (!file_exists($file)) return false;

        $data = json_decode(file_get_contents($file), true);
        return $data && time() <= ($data['expires_at'] ?? 0);
    }

    public static function cancel(string $phone): void
    {
        $file = self::file($phone);
        if (file_exists($file)) unlink($file);
    }
}
