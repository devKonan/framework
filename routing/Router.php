<?php
namespace Briko\Routing;

use Briko\Http\Request;
use Briko\Http\Middleware\Pipeline;

class Router
{
    private array  $routes          = [];
    private string $currentPrefix   = '';
    private array  $groupMiddlewares = [];

    // ─── Route registration ───────────────────────────────────────────────────

    public function get(string $uri, mixed $action, array $middlewares = []): void
    {
        $this->add('GET', $uri, $action, $middlewares);
    }

    public function post(string $uri, mixed $action, array $middlewares = []): void
    {
        $this->add('POST', $uri, $action, $middlewares);
    }

    public function put(string $uri, mixed $action, array $middlewares = []): void
    {
        $this->add('PUT', $uri, $action, $middlewares);
    }

    public function patch(string $uri, mixed $action, array $middlewares = []): void
    {
        $this->add('PATCH', $uri, $action, $middlewares);
    }

    public function delete(string $uri, mixed $action, array $middlewares = []): void
    {
        $this->add('DELETE', $uri, $action, $middlewares);
    }

    // ─── Route groups ─────────────────────────────────────────────────────────

    /**
     * $attrs keys: 'prefix' (string), 'middleware' (array)
     *
     * $router->group(['prefix' => '/api/v1', 'middleware' => [AuthMiddleware::class]], function ($r) {
     *     $r->get('/users', [UserController::class, 'index']);
     * });
     */
    public function group(array $attrs, callable $fn): void
    {
        $prevPrefix      = $this->currentPrefix;
        $prevMiddlewares = $this->groupMiddlewares;

        $this->currentPrefix    = $prevPrefix . ($attrs['prefix'] ?? '');
        $this->groupMiddlewares = array_merge($prevMiddlewares, $attrs['middleware'] ?? []);

        $fn($this);

        $this->currentPrefix    = $prevPrefix;
        $this->groupMiddlewares = $prevMiddlewares;
    }

    // ─── Dispatch ─────────────────────────────────────────────────────────────

    public function dispatch(Request $request): mixed
    {
        $method = $request->method;
        $uri    = $request->uri;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (!preg_match($route['pattern'], $uri, $matches)) continue;

            $params = [];
            foreach ($route['params'] as $name) {
                $params[$name] = $matches[$name] ?? null;
            }
            $request->params = $params;

            $action      = $route['action'];
            $middlewares = $route['middlewares'];

            $destination = function (Request $req) use ($action): mixed {
                if (is_callable($action)) {
                    return call_user_func($action, $req);
                }
                if (is_array($action) && count($action) === 2) {
                    [$class, $method] = $action;
                    $controller = new $class();
                    return $controller->$method($req);
                }
                return ['error' => 'Action de route invalide'];
            };

            $pipeline = new Pipeline();
            return $pipeline->send($middlewares)->then($destination)($request);
        }

        return ['error' => '404 - Route non trouvée', 'code' => 404];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function add(string $method, string $uri, mixed $action, array $middlewares): void
    {
        $fullUri = $this->currentPrefix . $uri;

        $this->routes[] = [
            'method'      => $method,
            'uri'         => $fullUri,
            'pattern'     => $this->toRegex($fullUri),
            'params'      => $this->extractParams($fullUri),
            'action'      => $action,
            'middlewares' => array_merge($this->groupMiddlewares, $middlewares),
        ];
    }

    private function toRegex(string $uri): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    private function extractParams(string $uri): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $uri, $matches);
        return $matches[1];
    }
}
