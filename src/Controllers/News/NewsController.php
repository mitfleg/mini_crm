<?php

namespace App\Controllers\News;

use App\DomainInfra\BaseController;
use App\HTTP;

class NewsController extends BaseController {

    public static function list(HTTP\Request $request, HTTP\Response $response) {
        $response->json(
            [
                'news' => []
            ]
        );
    }
}