<?php

namespace App\Service\Entity;

use App\Entity\Product;

class ProductService extends BaseEntityService
{
    protected $entityClass = Product::class;

    public function create($properties = []): BaseEntityService
    {
        parent::create($properties);

        $this->getEntity()->setSlug($this->generateSlug($this->getEntity()->getName()));

        dump($this);
        die();

        return $this;
    }

    private function generateSlug($name)
    {
        return str_replace(' ', '', strtolower($name));
    }
}