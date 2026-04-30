<?php
namespace Briko\core;

use Briko\gbaka\Kernel;
use Briko\core\Logger;

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
