<?php

namespace App\Service\Entity;

use Doctrine\Common\Persistence\ObjectManager;

abstract class BaseEntityService
{
    protected $entityClass;
    protected $om;
    protected $errors = [];
    protected $entity;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;

        if (empty($this->entityClass)) {
            throw new \Exception("Missing entity class.");
        }
    }

    public function create($properties = []): BaseEntityService
    {
        $this->setEntity(new $this->entityClass());
        return $this->setProperties($properties);
    }

    public function setProperties($properties = [])
    {
        if ($this->getEntity()) {
            foreach ($properties as $property => $value) {
                if (strtolower($property) === 'id') {
                    continue;
                }

                $setterMethod = 'set' . $property;
                if (method_exists($this->getEntity(), $setterMethod)) {
                    $this->getEntity()->$setterMethod($value);
                }
            }
        }

        return $this;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity): void
    {
        if (!is_a($entity, $this->entityClass)) {
            throw new \Exception('Setting invalid entity.  Expecting entity to be of type: ' . $this->entityClass);
        }

        $this->entity = $entity;
    }

    public function save(): bool
    {
        if (!empty($this->entity)) {
            // Save entity
            $this->om->persist($this->entity);
            $this->om->flush();

            // Reset the errors array after saving is successful
            $this->errors = [];

            return true;
        }

        $this->errors[] = 'The entity being saved was empty.';

        return false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}