<?php
namespace Briko\Sms\Drivers;

use RuntimeException;

abstract class AbstractDriver implements SmsDriverInterface
{
    protected function post(string $url, array $fields, array $headers = [], ?string $basicAuth = null): array
    {
        if (!extension_loaded('curl')) {
            return $this->fgcPost($url, $fields, $headers, $basicAuth);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($basicAuth) {
            curl_setopt($ch, CURLOPT_USERPWD, $basicAuth);
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new RuntimeException("SMS cURL error: $err");

        return ['code' => $code, 'body' => $body ?: ''];
    }

    private function fgcPost(string $url, array $fields, array $headers, ?string $basicAuth): array
    {
        $payload = http_build_query($fields);

        $allHeaders = array_merge(
            ['Content-Type: application/x-www-form-urlencoded'],
            $headers
        );

        if ($basicAuth) {
            $allHeaders[] = 'Authorization: Basic ' . base64_encode($basicAuth);
        }

        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $allHeaders),
            'content' => $payload,
            'timeout' => 15,
        ]]);

        $body = @file_get_contents($url, false, $ctx);
        $code = isset($http_response_header) ? (int) explode(' ', $http_response_header[0])[1] : 0;

        if ($body === false) throw new RuntimeException("SMS HTTP error : connexion impossible vers $url");

        return ['code' => $code, 'body' => $body];
    }
}
