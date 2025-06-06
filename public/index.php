<?php

define('ROOT_DIR', dirname(__DIR__, 1));

require_once ROOT_DIR . '/src/configs/cfg.php';
require ROOT_DIR . '/vendor/autoload.php';

use App\Routes\Router;
use App\DomainInfra\Exceptions\BaseException;

function handleError($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(
        [
            'result' => false,
            'error' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    );
    exit;
}
set_error_handler('handleError');

try {
    $router = new Router();
    require_once ROOT_DIR . '/src/Routes/routers.php';
    initRouters($router);

    $response = [
        'status' => 'success',
        'message' => 'Мини-CRM API работает'
    ];

    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (BaseException $e) {
    http_response_code($e->getCode());
    echo json_encode(
        [
            'result' => false,
            'error' => $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(
        [
            'result' => false,
            'error' => $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
}
