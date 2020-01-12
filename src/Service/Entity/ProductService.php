<?php

declare(strict_types=1);

namespace App\Service\Entity;

use App\Entity\Product;

class ProductService extends BaseEntityService
{
    protected $entityClass = Product::class;

    public function create($properties = []): BaseEntityService
    {
        parent::create($properties);

        /** @var Product $product */
        $product = $this->getEntity();
        $product->setSlug($this->slugger->slug($product->getName())->lower()->toString());

        return $this;
    }
}
