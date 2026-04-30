<?php
namespace Briko\Http;

class Request
{
    public string $uri;
    public string $method;
    public array  $params  = [];
    public array  $query   = [];
    public array  $post    = [];
    public array  $files   = [];
    private ?array $jsonBody = null;

    public static function capture(): static
    {
        $req         = new static();
        $req->uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $req->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $req->query  = $_GET;
        $req->post   = $_POST;
        $req->files  = $_FILES;
        return $req;
    }

    // Reconstruit une requête depuis les données stockées (utilisé par le CLI sync)
    public static function fromArray(string $method, string $uri, array $payload = []): static
    {
        $req           = new static();
        $req->uri      = $uri;
        $req->method   = strtoupper($method);
        $req->post     = $payload;
        $req->jsonBody = $payload;
        return $req;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key]
            ?? $this->query[$key]
            ?? $this->params[$key]
            ?? $this->body()[$key]
            ?? $default;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function body(): array
    {
        if ($this->jsonBody === null) {
            $raw            = file_get_contents('php://input');
            $decoded        = json_decode($raw ?: '', true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        }
        return $this->jsonBody;
    }

    public function isJson(): bool
    {
        return str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public function isLowBandwidth(): bool
    {
        return ($this->query['lb'] ?? '') === '1'
            || ($this->query['compact'] ?? '') === '1'
            || ($_SERVER['HTTP_X_LOW_BANDWIDTH'] ?? '') === '1';
    }

    public function wantsFields(): ?array
    {
        $fields = $this->query['fields'] ?? null;
        if (!$fields) return null;
        return array_filter(array_map('trim', explode(',', $fields)));
    }

    public function acceptsGzip(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip')
            && extension_loaded('zlib');
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->body(), $this->params);
    }
}
