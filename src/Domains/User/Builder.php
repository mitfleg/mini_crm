<?php

namespace App\Domains\User;

use App\DomainInfra\BaseFacade;

class Builder extends BaseFacade {

    protected function buildAuthUseCase(): Usecases\Auth {
        $jwtKey = $_ENV['JWT_KEY'];
        $jwtIssuer = $_ENV['JWT_ISSUER'];
        return new Usecases\Auth($jwtKey, $jwtIssuer);
    }
}