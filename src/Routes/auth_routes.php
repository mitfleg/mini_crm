<?php

use App\Routes\Router;

function initAuthRoutes(Router $router) {
    $router->post('/api/auth/login', 'Auth\\AuthController', 'login');
    $router->post('/api/auth/register', 'Auth\\AuthController', 'register');
}