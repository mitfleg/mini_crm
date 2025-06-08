<?php

namespace App\DomainInfra;

use App\DomainInfra\Exceptions\BaseException;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseModel {

    public const TABLE = '';

    protected ?int $id = null;
    protected ?string $created_at = null;
    protected ?string $updated_at = null;
    protected ?string $deleted_at = null;
    private array $originalValues = [];

    public function __construct($bean = null) {
        if( $bean ) {
            $this->id = $bean->id;
            $this->created_at = $bean->created_at;
            $this->updated_at = $bean->updated_at;
            $this->deleted_at = $bean->deleted_at;

            $reflection = new ReflectionClass($this);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);

            foreach($properties as $prop) {
                $name = $prop->getName();

                if( isset($bean->$name) ) {
                    $prop->setAccessible(true);
                    $prop->setValue($this, $bean->$name);
                }
            }

            $this->originalValues = $this->getFields();
        }
        else {
            $this->created_at = $this->created_at ?? date('Y-m-d H:i:s');
        }
    }

    public function save(): static {
        $this->validate();
        $this->updated_at = date('Y-m-d H:i:s');

        if( $this->id ) {
            $bean = \R::load(static::TABLE, $this->id);
        }
        else {
            $bean = \R::dispense(static::TABLE);
        }

        foreach($this->getFields() as $field => $value) {
            if( $field === 'id' && !$this->id ) {
                continue;
            }

            $bean->$field = $value;
        }

        $this->id = \R::store($bean);
        $this->originalValues = $this->getFields();

        return $this;
    }

    public function toArray(bool $includeHidden = false): array {
        $data = [];

        foreach($this->getProperties() as $prop) {
            $name = $prop->getName();
            $annotations = $this->getAnnotations($prop);

            if( !$includeHidden && in_array('hidden', $annotations) ) continue;

            $prop->setAccessible(true);
            $data[$name] = $prop->getValue($this);
        }

        return $data;
    }

    public static function getByID(int $id): static {
        $bean = \R::findOne(static::TABLE, 'id = ?', [$id]);

        if( !$bean ) throw new BaseException('Not found');

        return new static($bean);
    }

    public static function getBy(array $conditions): static {
        [$where, $values] = static::getWhere($conditions);
        $bean = \R::findOne(static::TABLE, $where, $values);

        if( !$bean ) throw new BaseException('Not found');

        return new static($bean);
    }

    public static function findByID(int $id): ?static {
        $bean = \R::findOne(static::TABLE, 'id = ?', [$id]);
        return $bean ? new static($bean) : null;
    }

    public static function findOneBy(array $conditions): ?static {
        [$where, $values] = static::getWhere($conditions);
        $bean = \R::findOne(static::TABLE, $where, $values);
        return $bean ? new static($bean) : null;
    }

    public static function findAll(): array {
        $beans = \R::findAll(static::TABLE);
        return array_map(fn($bean) => new static($bean), $beans);
    }

    public static function findAllBy(array $conditions): array {
        [$where, $values] = static::getWhere($conditions);
        $beans = \R::findAll(static::TABLE, $where, $values);
        return array_map(fn($bean) => new static($bean), $beans);
    }

    public function delete(bool $soft = false): void {
        if( $soft ) {
            $this->deleted_at = date('Y-m-d H:i:s');
            $this->save();
        }
        else {
            if( $this->id ) {
                $bean = \R::load(static::TABLE, $this->id);

                if( $bean->id ) {
                    \R::trash($bean);
                }
            }
        }
    }

    protected function validate(): void {
        foreach($this->getProperties() as $prop) {
            $annotations = $this->getAnnotations($prop);
            $name = $prop->getName();

            $prop->setAccessible(true);
            $value = $prop->getValue($this);

            if( in_array('required', $annotations) && empty($value) ) {
                throw new BaseException("Field '$name' is required.");
            }

            foreach($annotations as $annotation) {
                if( $annotation === 'unique' ) {
                    $isNewRecord = empty($this->id);
                    $fieldChanged = !isset($this->originalValues[$name]) || $this->originalValues[$name] !== $value;

                    if( $isNewRecord || $fieldChanged ) {
                        $existing = \R::findOne(static::TABLE, "$name = ?", [$value]);

                        if( $existing && $existing->id !== $this->id ) {
                            throw new BaseException("Field '$name' must be unique.");
                        }
                    }
                }
            }
        }
    }

    protected function getAnnotations(ReflectionProperty $prop): array {
        preg_match_all('/@(\w+)(?:\s+([\w@.]+))?/', $prop->getDocComment() ?: '', $matches);
        return array_map(fn($name, $arg) => trim("$name $arg"), $matches[1], $matches[2]);
    }

    /**
     * @return ReflectionProperty[]
     */
    protected function getProperties(): array {
        return (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);
    }

    protected function getFields(): array {
        $data = [];

        foreach($this->getProperties() as $prop) {
            $prop->setAccessible(true);
            $data[$prop->getName()] = $prop->getValue($this);
        }

        return $data;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public static function generateJsonSchema(): array {
        $class = new ReflectionClass(static::class);
        $props = $class->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);
        $schema = ['type' => 'object', 'properties' => [], 'required' => []];

        foreach($props as $prop) {
            $annotations = (new static())->getAnnotations($prop);
            $name = $prop->getName();
            $type = 'string';
            $schema['properties'][$name] = [];

            if( in_array('readonly', $annotations) ) {
                $schema['properties'][$name]['readOnly'] = true;
            }

            if( in_array('hidden', $annotations) ) {
                $schema['properties'][$name]['writeOnly'] = true;
            }

            foreach($annotations as $an) {
                if( str_starts_with($an, 'rule email') ) {
                    $schema['properties'][$name]['format'] = 'email';
                }
            }

            if( in_array('required', $annotations) ) {
                $schema['required'][] = $name;
            }

            $schema['properties'][$name]['type'] = $type;
        }

        return $schema;
    }

    private static function getWhere(array $conditions): array {
        $where = '';
        $values = [];

        foreach($conditions as $field => $value) {
            $where .= "$field = ? AND ";
            $values[] = $value;
        }

        $where = rtrim($where, ' AND ');
        return [$where, $values];
    }
}
