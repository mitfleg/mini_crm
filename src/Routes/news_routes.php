<?php

use App\Routes\Router;

function initNewsRoutes(Router $router) {
    $router->get('/api/news', 'News\NewsController', 'list');
}