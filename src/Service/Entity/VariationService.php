<?php

declare(strict_types=1);

namespace App\Service\Entity;

use App\Entity\Product;
use App\Entity\Variation;
use Goutte\Client;

class VariationService extends BaseEntityService
{
    protected $entityClass = Variation::class;
    /** @var Product */
    private $product;

    public function create($properties = []): BaseEntityService
    {
        parent::create($properties);

        /** @var Variation $variation */
        $variation = $this->getEntity();

        $variation->setName($this->getVariationName($properties['sku']));
        $variation->setInStock($this->isInStock($properties['availability']));
        $variation->setSlug($this->slugGenerator->generateSlug($this->getVariationName($properties['sku'])));
        $variation->setProduct($this->product);

        return $this;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    private function isInStock($availability): int
    {
        if (strpos($availability, 'InStock')) {
            return 1;
        }

        return 0;
    }

    private function getVariationName($sku)
    {
        $client = new Client();
        $crawler = $client->request('GET', 'https://nl.myprotein.com/'.$sku.'.images?variation=false&stringTemplatePath=components/athenaProductImageCarousel/athenaProductImageCarousel');

        return $crawler->filter('.athenaProductImageCarousel_imagePreview')->attr('alt');
    }
}
