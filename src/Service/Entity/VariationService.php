<?php

declare(strict_types=1);

namespace App\Service\Entity;

use App\Entity\Product;
use App\Entity\Variation;
use Goutte\Client;
use function Symfony\Component\String\u;

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
        $variation->setName($this->processVariationName($this->product, $properties['sku']));
        $variation->setInStock($this->isInStock($properties['availability']));
        $variation->setSlug($this->slugger->slug($this->processVariationName($this->product, $properties['sku']))->lower()->toString());
        $variation->setProduct($this->product);

        return $this;
    }

    public function update($entity, array $properties): BaseEntityService
    {
        parent::update($entity, $properties);

        /** @var Variation $variation */
        $variation = $this->getEntity();
//        $variation->setName($this->processVariationName($variation->getProduct(), $properties['sku']));
//        $variation->setSlug($this->slugger->slug($this->processVariationName($variation->getProduct(), $properties['sku']))->lower()->toString());
        $variation->setInStock($this->isInStock($properties['availability']));

        return $this;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function isInStock($availability): int
    {
        if (u($availability)->match('/(?:InStock)/')) {
            return 1;
        }

        return 0;
    }

    private function processVariationName(Product $product, $sku)
    {
        $client = new Client();
        $crawler = $client->request('GET', 'https://nl.myprotein.com/'.$sku.'.images?variations=false&stringTemplatePath=components/athenaProductImageCarousel/athenaProductImageCarousel');

        return str_replace(['New -', 'New –', 'Doos -', $product->getName().' - '], '', $crawler->filter('.athenaProductImageCarousel_imagePreview')->attr('alt'));
    }
}
