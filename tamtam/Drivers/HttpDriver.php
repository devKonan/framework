<?php
namespace Briko\tamtam\Drivers;

use Briko\tamtam\SmsResult;
use RuntimeException;

/**
 * Driver HTTP générique — compatible avec n'importe quel provider local.
 * Configurez les champs via .env pour correspondre à l'API de votre opérateur.
 *
 * .env requis : SMS_HTTP_URL
 * .env optionnels :
 *   SMS_HTTP_FIELD_TO      = numero       (défaut: to)
 *   SMS_HTTP_FIELD_MSG     = contenu      (défaut: message)
 *   SMS_HTTP_FIELD_FROM    = expediteur   (défaut: from)
 *   SMS_HTTP_AUTH_FIELD    = champ clé API (défaut: apikey)
 *   SMS_HTTP_AUTH_VALUE    = valeur clé API
 *   SMS_HTTP_SUCCESS_CODE  = code HTTP de succès (défaut: 200)
 *
 * Exemple Orange CI, MTN CI, Moov, etc.
 */
class HttpDriver extends AbstractDriver
{
    private string $url;
    private string $fieldTo;
    private string $fieldMsg;
    private string $fieldFrom;
    private string $authField;
    private string $authValue;
    private int    $successCode;

    public function __construct()
    {
        $this->url         = env('SMS_HTTP_URL', '');
        $this->fieldTo     = env('SMS_HTTP_FIELD_TO',    'to');
        $this->fieldMsg    = env('SMS_HTTP_FIELD_MSG',   'message');
        $this->fieldFrom   = env('SMS_HTTP_FIELD_FROM',  'from');
        $this->authField   = env('SMS_HTTP_AUTH_FIELD',  'apikey');
        $this->authValue   = env('SMS_HTTP_AUTH_VALUE',  '');
        $this->successCode = (int) env('SMS_HTTP_SUCCESS_CODE', '200');
    }

    public function send(string $to, string $message, ?string $from = null): SmsResult
    {
        if (!$this->url) {
            throw new RuntimeException('HttpDriver : SMS_HTTP_URL manquant dans .env');
        }

        $fields = [
            $this->fieldTo  => $to,
            $this->fieldMsg => $message,
        ];

        if ($from) $fields[$this->fieldFrom] = $from;
        if ($this->authValue) $fields[$this->authField] = $this->authValue;

        $res  = $this->post($this->url, $fields);
        $body = json_decode($res['body'], true);
        $ok   = $res['code'] === $this->successCode;

        return new SmsResult(
            success:   $ok,
            messageId: $body['id'] ?? $body['messageId'] ?? '',
            info:      $body['message'] ?? $body['status'] ?? (string) $res['code'],
            raw:       $body ?? []
        );
    }
}
