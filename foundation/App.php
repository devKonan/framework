<?php
namespace Briko\Foundation;

use Briko\Http\Kernel;
use Briko\Foundation\Logger;

class App
{
    public Container $container;

    public function __construct()
    {
        $this->container = new Container();
        $this->boot();
    }

    private function boot(): void
    {
        Env::load(base_path('.env'));
        Logger::boot();
    }

    public function run(): void
    {
        $kernel = new Kernel($this);
        $kernel->handle();
    }
}
