<?php

namespace App\DomainInfra;

use App\HTTP;
use App\DomainInfra\Exceptions\ContractException;

class BaseContract {

    public function loadContract(HTTP\Request $request): self {
        $this->load($request->request);
        return $this;
    }

    public function buildResponse(array $data = []): self {
        $this->load($data);
        return $this;
    }

    public function toArray(): array {
        $class = new \ReflectionClass($this);
        $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
        $data = [];

        foreach($properties as $prop) {
            $name = $prop->getName();
            $prop->setAccessible(true);
            $data[$name] = $prop->getValue($this);
        }

        return $data;
    }

    private function load(array $data = []): void {
        $class = new \ReflectionClass($this);
        $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach($properties as $property) {
            $name = $property->getName();
            $type = $property->getType();
            $typeName = $type ? $type->getName() : null;
            $isTypeBuiltin = $type ? $type->isBuiltin() : false;
            $is_optional = $type ? $type->allowsNull() : false;

            if( array_key_exists($name, $data) ) {
                if( $isTypeBuiltin ) {
                    if( $typeName === 'bool' ) {
                        $dataValue = filter_var($data[$name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                        if( is_null($dataValue) && !$is_optional ) {
                            throw new ContractException("Неверный тип данных для поля: $name", 400);
                        }

                        $this->$name = $dataValue;
                    }
                    elseif( $typeName === 'int' ) {
                        $this->$name = (int) $data[$name];
                    }
                    elseif( $typeName === 'float' ) {
                        $this->$name = (float) $data[$name];
                    }
                    elseif( $typeName === 'string' ) {
                        $this->$name = (string) $data[$name];
                    }
                    else {
                        $this->$name = $data[$name];
                    }
                }
                else {
                    $this->$name = $data[$name];
                }
            }
            else {
                if( !$is_optional ) throw new ContractException("Необходимо заполнить поле: $name", 400);

                $this->$name = null;
            }
        }
    }
}
