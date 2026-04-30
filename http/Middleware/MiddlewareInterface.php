<?php
namespace Briko\Http\Middleware;

use Briko\Http\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next);
}
