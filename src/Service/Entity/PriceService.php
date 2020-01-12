<?php

declare(strict_types=1);

namespace App\Service\Entity;

use App\Entity\Price;
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
        $price->setVariation($this->variation);

        return $this;
    }

    public function setVariation(Variation $variation): self
    {
        $this->variation = $variation;

        return $this;
    }
}
