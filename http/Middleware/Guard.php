<?php
namespace Briko\Http\Middleware;

use Briko\Http\Request;

class Guard implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        // Example guard: block if header X-Block is present
        if (!empty($_SERVER['HTTP_X_BLOCK'])) {
            return ['error' => 'Blocked by Guard middleware'];
        }
        return $next($request);
    }
}
