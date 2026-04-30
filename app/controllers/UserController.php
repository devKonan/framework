<?php
namespace App\Controllers;

use Briko\Http\Request;
use Briko\Http\Response;

class UserController
{
    public function index(Request $request): array
    {
        return ['message' => 'UserController opérationnel'];
    }

    public function show(Request $request): array
    {
        $id = $request->param('id');
        return ['id' => $id];
    }

    public function store(Request $request): array
    {
        $data = $request->all();
        return ['created' => true, 'data' => $data];
    }

    public function update(Request $request): array
    {
        $id   = $request->param('id');
        $data = $request->all();
        return ['updated' => true, 'id' => $id];
    }

    public function destroy(Request $request): array
    {
        $id = $request->param('id');
        return ['deleted' => true, 'id' => $id];
    }
}
