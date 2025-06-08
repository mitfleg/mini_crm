<?php

use App\Routes\Router;
require_once __DIR__ . '/auth_routes.php';
require_once __DIR__ . '/news_routes.php';

function initRouters(Router $router) {
    initAuthRoutes($router);
    initNewsRoutes($router);

    $router->dispatch();
}