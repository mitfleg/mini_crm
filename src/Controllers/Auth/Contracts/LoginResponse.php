<?php

namespace App\Controllers\Auth\Contracts;

use App\DomainInfra\ContractResponse;

class LoginResponse extends ContractResponse {

    public string $access_token;
    public string $token_type;
    public int $expires_in;
    public array $user;
}