<?php

declare(strict_types=1);

namespace App\Service\Entity;

use App\Entity\Price;
use App\Entity\Product;
use App\Entity\Variation;

class PriceService extends BaseEntityService
{
    protected $entityClass = Price::class;
    /** @var Variation */
    private $variation;

    public function create($properties = []): BaseEntityService
    {
        parent::create($properties);

        /** @var Price $price */
        $price = $this->getEntity();

        /** @var Variation $variation */
        $variation = $this->variation;
        $price->setVariation($variation);

        /** @var Product $product */
        $product = $variation->getProduct();
        $product->setUpdated($price->getDate());

        return $this;
    }

    public function setVariation(Variation $variation): self
    {
        $this->variation = $variation;

        return $this;
    }
}
