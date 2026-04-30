<?php
namespace App\Controllers;

use Briko\Http\Request;
use Briko\Http\Response;

class HomeController
{
    public function index(Request $request): void
    {
        Response::html(view('welcome', [
            'appName' => env('APP_NAME', 'Brikocode'),
            'appEnv'  => env('APP_ENV',  'local'),
            'appUrl'  => env('APP_URL',  'http://localhost:8000'),
            'version' => '0.2',
        ]));
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
