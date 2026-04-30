<?php
namespace Briko\gbaka\Middleware;

use Briko\gbaka\Request;

class Pipeline
{
    protected array $middlewares = [];

    public function send(array $middlewares): self
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function then(callable $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, $middleware) {
                return function ($request) use ($middleware, $next) {
                    if (is_string($middleware)) {
                        $middleware = new $middleware();
                    }
                    return $middleware->handle($request, $next);
                };
            },
            $destination
        );

        return $pipeline;
    }
}
