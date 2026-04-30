<?php
namespace Briko\Http\Middleware;

use Briko\Http\Request;
use Briko\Foundation\Logger;

class HttpLogger implements MiddlewareInterface
{
    // Seuil en ms au-delà duquel la requête est marquée "slow"
    private int $slowThreshold;

    public function __construct(int $slowThresholdMs = 500)
    {
        $this->slowThreshold = $slowThresholdMs;
    }

    public function handle(Request $request, callable $next): mixed
    {
        $start = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $duration = $this->ms($start);
            Logger::channel('http')->error("{$request->method} {$request->uri} — exception", [
                'duration_ms' => $duration,
                'ip'          => $this->ip(),
                'exception'   => $e->getMessage(),
                'file'        => $e->getFile() . ':' . $e->getLine(),
            ]);
            throw $e;
        }

        $duration = $this->ms($start);
        $isError  = isset($response['error']) || isset($response['_offline']);
        $isSlow   = $duration >= $this->slowThreshold;

        $ctx = [
            'duration_ms' => $duration,
            'ip'          => $this->ip(),
            'status'      => $isError ? 'error' : 'ok',
        ];

        if ($isSlow) {
            $ctx['slow'] = true;
        }

        if ($request->method !== 'GET') {
            // Log le payload des écritures (sans mots de passe)
            $ctx['payload'] = $this->sanitize($request->all());
        }

        $level = match (true) {
            $isSlow && $isError => Logger::ERROR,
            $isSlow             => Logger::WARNING,
            $isError            => Logger::WARNING,
            default             => Logger::INFO,
        };

        Logger::channel('http')->{strtolower($level)}(
            "{$request->method} {$request->uri}",
            $ctx
        );

        return $response;
    }

    private function ms(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    private function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'cli';
    }

    private function sanitize(array $data): array
    {
        foreach (['password', 'password_confirmation', 'token', 'secret', 'card_number'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***';
            }
        }
        return $data;
    }
}
