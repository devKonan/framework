<?php
namespace Briko\Http;

use Briko\Foundation\App;
use Briko\Routing\Router;

class Kernel
{
    protected App    $app;
    protected Router $router;

    public function __construct(App $app)
    {
        $this->app    = $app;
        $this->router = new Router();
        $router       = $this->router;
        require base_path('app/routes.php');
    }

    public function handle(): void
    {
        $request  = Request::capture();
        $response = $this->dispatch($request);
        if ($response !== null) {
            Response::send($response);
        }
    }

    // Dispatch sans passer par $_SERVER — utilisé par le CLI sync
    public function dispatch(Request $request): mixed
    {
        return $this->router->dispatch($request);
    }
}
