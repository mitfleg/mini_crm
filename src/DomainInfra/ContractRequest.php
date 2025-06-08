<?php

namespace App\DomainInfra;

use App\HTTP;

class ContractRequest extends BaseContract {

    public function __construct(HTTP\Request $request) {
        $this->loadContract($request);
    }
}
