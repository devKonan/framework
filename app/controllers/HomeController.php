<?php
namespace App\Controllers;

use Briko\Http\Request;
use Briko\Http\Response;

class HomeController
{
    public function index(Request $request): void
    {
        $data = [
            'appName' => env('APP_NAME', 'Brikocode'),
            'appEnv'  => env('APP_ENV',  'local'),
            'appUrl'  => env('APP_URL',  'http://localhost:8000'),
            'version' => '0.1 PRO',
        ];

        ob_start();
        extract($data);
        include base_path('app/views/welcome.php');
        $html = ob_get_clean();

        Response::html($html);
    }

    public function api(Request $request): array
    {
        return [
            'status'  => 'ok',
            'app'     => env('APP_NAME', 'Brikocode'),
            'version' => '0.1-pro',
            'php'     => PHP_VERSION,
        ];
    }
}
