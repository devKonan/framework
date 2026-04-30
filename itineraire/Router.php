<?php
namespace Briko\itineraire;

use Briko\gbaka\Request;
use Briko\gbaka\Middleware\Pipeline;

class Router
{
    private array $routes = [];

    public function get(string $uri, $action, array $middlewares = []): void
    {
        $this->add('GET', $uri, $action, $middlewares);
    }

    public function post(string $uri, $action, array $middlewares = []): void
    {
        $this->add('POST', $uri, $action, $middlewares);
    }

    public function put(string $uri, $action, array $middlewares = []): void
    {
        $this->add('PUT', $uri, $action, $middlewares);
    }

    public function patch(string $uri, $action, array $middlewares = []): void
    {
        $this->add('PATCH', $uri, $action, $middlewares);
    }

    public function delete(string $uri, $action, array $middlewares = []): void
    {
        $this->add('DELETE', $uri, $action, $middlewares);
    }

    private function add(string $method, string $uri, $action, array $middlewares): void
    {
        $this->routes[] = [
            'method'      => $method,
            'uri'         => $uri,
            'pattern'     => $this->toRegex($uri),
            'params'      => $this->extractParams($uri),
            'action'      => $action,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(Request $request)
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

            $destination = function ($req) use ($action) {
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
