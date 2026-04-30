<?php
namespace Briko\gbaka\Middleware;

use Briko\gbaka\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next);
}
