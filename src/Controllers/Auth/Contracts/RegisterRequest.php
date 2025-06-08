<?php

namespace App\Controllers\Auth\Contracts;

use App\DomainInfra\ContractRequest;

class RegisterRequest extends ContractRequest {

    public string $email;
    public string $password;
    public string $login;
}