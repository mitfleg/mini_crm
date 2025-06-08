<?php

namespace App\DomainInfra;

use App\HTTP;

class BaseFacade {

    protected HTTP\Request $request;

    public function __construct(HTTP\Request $request) {
        $this->request = $request;
    }
}