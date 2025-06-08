<?php

namespace App\Controllers\Auth\Contracts;

use App\DomainInfra\ContractRequest;

class LoginRequest extends ContractRequest {

    public string $login;
    public string $password;
} 