<?php
namespace Briko\Payment\Drivers;

abstract class AbstractDriver
{
    // ─── HTTP helpers (zéro dépendance) ───────────────────────────────────────

    protected function post(string $url, array $payload, array $headers = []): array
    {
        return $this->request('POST', $url, json_encode($payload), array_merge(
            ['Content-Type: application/json', 'Accept: application/json'],
            $headers
        ));
    }

    protected function postForm(string $url, array $payload, array $headers = []): array
    {
        return $this->request('POST', $url, http_build_query($payload), array_merge(
            ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            $headers
        ));
    }

    protected function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, '', array_merge(
            ['Accept: application/json'],
            $headers
        ));
    }

    private function request(string $method, string $url, string $body, array $headers): array
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('Extension PHP curl requise pour le module Payment.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Erreur réseau : {$error}");
        }

        $decoded = json_decode($response ?: '{}', true);

        return [
            'code' => $code,
            'body' => is_array($decoded) ? $decoded : ['_raw' => $response],
        ];
    }

    protected function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
