<?php

declare(strict_types=1);

namespace App\Service\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

abstract class BaseEntityService
{
    protected $entityClass;
    protected $om;
    protected $errors = [];
    protected $entity;
    protected $slugger;

    public function __construct(EntityManagerInterface $om, SluggerInterface $slugger)
    {
        $this->om = $om;
        $this->slugger = $slugger;

        if (empty($this->entityClass)) {
            throw new \Exception('Missing entity class.');
        }
    }

    public function create($properties = []): self
    {
        $this->setEntity(new $this->entityClass());

        return $this->setProperties($properties);
    }

    public function update($entity, array $properties): self
    {
        $this->setEntity($entity);

        return $this->setProperties($properties);
    }

    public function setProperties($properties = []): self
    {
        if ($this->getEntity()) {
            foreach ($properties as $property => $value) {
                if ('id' === strtolower($property)) {
                    continue;
                }

                $setterMethod = 'set'.$property;
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
            throw new \Exception('Setting invalid entity.  Expecting entity to be of type: '.$this->entityClass);
        }

        $this->entity = $entity;
    }

    public function save()
    {
        if (!empty($this->entity)) {
            $this->om->persist($this->entity);
            $this->om->flush();

            $this->errors = [];

            return $this->entity;
        }

        $this->errors[] = 'The entity being saved was empty.';
    }

    public function clear(): void
    {
        $this->om->clear();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
