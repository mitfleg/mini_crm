<?php

namespace App\Controllers\Auth;

use App\DomainInfra\BaseController;
use App\HTTP;
use App\Domains\User;

class AuthController extends BaseController {

    public static function _validateAuth(HTTP\Request $request): bool {
        return true;
    }

    public static function register(HTTP\Request $request, HTTP\Response $response) {
        /** @var Contracts\RegisterRequest $requestData */
        $requestData = $request->loadFromContract(Contracts\RegisterRequest::class);
        $userFacade = new User\FacadeAuth($request);
        $data = $userFacade->register($requestData->email, $requestData->password, $requestData->login);
        $answer = $request->buildResponse(Contracts\RegisterResponse::class, $data);
        $response->object($answer);
    }

    public static function login(HTTP\Request $request, HTTP\Response $response) {
        /** @var Contracts\LoginRequest $requestData */
        $requestData = $request->loadFromContract(Contracts\LoginRequest::class);
        $userFacade = new User\FacadeAuth($request);
        $data = $userFacade->login($requestData->login, $requestData->password);
        $answer = $request->buildResponse(Contracts\LoginResponse::class, $data);
        $response->object($answer);
    }
}