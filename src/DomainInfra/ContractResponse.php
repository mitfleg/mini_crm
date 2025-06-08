<?php

namespace App\DomainInfra;


class ContractResponse extends BaseContract {

    public function __construct(array $data = []) {
        $this->buildResponse($data);
    }
}
