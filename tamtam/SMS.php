<?php
namespace Briko\tamtam;

/**
 * Facade SMS — tam-tam numérique de Brikocode.
 *
 * Usage :
 *   SMS::to('+2250700000000')->send('Votre commande est confirmée.');
 *   SMS::to(['+2250700000000', '+2250800000000'])->from('MonApp')->send('Promo !');
 *
 * OTP :
 *   $code = SMS::otp('+2250700000000');       // génère + envoie
 *   SMS::verifyOtp('+2250700000000', $code);   // true / false
 */
class SMS
{
    public static function to(string|array $numbers): SmsMessage
    {
        return new SmsMessage($numbers);
    }

    /**
     * Génère un OTP, l'envoie par SMS et retourne le code en clair.
     * Stocke le hash dans storage/otp/ — valide {ttl} minutes (défaut 5 min).
     */
    public static function otp(
        string $phone,
        int    $length     = 6,
        int    $ttlMinutes = 5,
        string $prefix     = ''
    ): string {
        $code    = OtpManager::generate($phone, $length, $ttlMinutes);
        $appName = env('APP_NAME', 'Brikocode');
        $prefix  = $prefix ?: "[$appName] Votre code : ";

        static::to($phone)->send($prefix . $code);
        return $code;
    }

    /**
     * Vérifie un OTP. Supprime le code si valide ou si trop de tentatives.
     */
    public static function verifyOtp(string $phone, string $code): bool
    {
        return OtpManager::verify($phone, $code);
    }

    public static function otpPending(string $phone): bool
    {
        return OtpManager::isPending($phone);
    }

    public static function cancelOtp(string $phone): void
    {
        OtpManager::cancel($phone);
    }
}
