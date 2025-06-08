<?php

namespace App\Routes;

use App\HTTP;
use App\DomainInfra\Exceptions\BaseException;

class Router {

    private array $routes = [];

    public function get(string $path, string $controller, string $action): void {
        $this->addRoute('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void {
        $this->addRoute('POST', $path, $controller, $action);
    }

    public function put(string $path, string $controller, string $action): void {
        $this->addRoute('PUT', $path, $controller, $action);
    }

    public function delete(string $path, string $controller, string $action): void {
        $this->addRoute('DELETE', $path, $controller, $action);
    }

    private function addRoute(string $method, string $path, string $controller, string $action): void {
        $this->routes[$method][$path] = [
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch(): void {
        $request = new HTTP\Request();
        $response = new HTTP\Response();

        $callback = $this->routes[$request->method][$request->path] ?? null;

        if( $callback ) {
            $controllerClass = "App\\Controllers\\" . $callback['controller'];
            $controller = new $controllerClass();
            $action = $callback['action'];
            $validateAuth = $controller::_validateAuth($request);

            if( !$validateAuth ) {
                throw new BaseException('Unauthorized', 401);
            }

            $controller::$action($request, $response);
        }
    }
}