<?php

use Briko\village\controllers\HomeController;
use Briko\village\controllers\UserController;
use Briko\gbaka\Middleware\Guard;
use Briko\gbaka\Middleware\OfflineFirst;

/** @var \Briko\itineraire\Router $router */

// Routes de base
$router->get('/', [HomeController::class, 'index']);
$router->get('/salut', fn () => ['message' => 'Gbaka est en route 🚐']);

// Route protégée par middleware
$router->get('/api', [HomeController::class, 'api'], [Guard::class]);

// Routes avec Offline-First : cache GET + queue POST/PUT/DELETE si DB down
$router->get('/users',         [UserController::class, 'index'],   [OfflineFirst::class]);
$router->get('/users/{id}',    [UserController::class, 'show'],    [OfflineFirst::class]);
$router->post('/users',        [UserController::class, 'store'],   [OfflineFirst::class]);
$router->put('/users/{id}',    [UserController::class, 'update'],  [OfflineFirst::class]);
$router->delete('/users/{id}', [UserController::class, 'destroy'], [OfflineFirst::class]);
