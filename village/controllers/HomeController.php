<?php
namespace Briko\village\controllers;

use Briko\gbaka\Request;

class HomeController
{
    public function index(Request $request)
    {
        return "🔥 Brikocode v0.1 PRO OK";
    }

    public function api(Request $request)
    {
        return [
            'status' => 'ok',
            'app' => 'brikocode',
            'version' => '0.1-pro'
        ];
    }
}
