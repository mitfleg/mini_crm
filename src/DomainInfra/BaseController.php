<?php

namespace App\DomainInfra;

use App\DomainInfra\Exceptions\BaseException;
use App\HTTP;

class BaseController {

    public static function _validateAuth(HTTP\Request $request): bool {
        if( $request->isAuth() ) {
            return true;
        }

        throw new BaseException('Пользователь не авторизован', 401);
    }
}
