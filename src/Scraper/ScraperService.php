<?php

declare(strict_types=1);

namespace App\Scraper;

use App\Entity\Price;
use App\Entity\Product;
use App\Entity\Variation;
use App\Repository\ProductRepository;
use App\Repository\ScrapeableProductRepository;
use App\Repository\VariationRepository;
use App\Service\Entity\PriceService;
use App\Service\Entity\ProductService;
use App\Service\Entity\VariationService;
use Goutte\Client;
use function Symfony\Component\String\u;

class ScraperService
{
    /** @var Client */
    private $client;
    /** @var ProductRepository */
    private $productRepository;
    /** @var ScrapeableProductRepository */
    private $scrapeableProductRepository;
    /** @var ProductService */
    private $productService;
    /** @var VariationRepository */
    private $variationRepository;
    /** @var VariationService */
    private $variationService;
    /** @var PriceService */
    private $priceService;

    public function __construct(
        ProductRepository $productRepository,
        VariationRepository $variationRepository,
        ScrapeableProductRepository $scrapeableProductRepository,
        ProductService $productService,
        VariationService $variationService,
        PriceService $priceService
    ) {
        $this->client = new Client();
        $this->productRepository = $productRepository;
        $this->scrapeableProductRepository = $scrapeableProductRepository;
        $this->productService = $productService;
        $this->variationRepository = $variationRepository;
        $this->variationService = $variationService;
        $this->priceService = $priceService;
    }

    public function startScraping(): void
    {
        $scrapeableProducts = $this->scrapeableProductRepository->findAll();
        foreach ($scrapeableProducts as $scrapeableProduct) {
            $crawler = $this->client->request('GET', $scrapeableProduct->getUrl());
            $crawler->filterXPath('//*[@type="application/ld+json"]')
                ->each(static function ($node) use (&$product): void {
                    $data = json_decode($node->text(), true);
                    if (array_key_exists('offers', $data)) {
                        $product = $data;
                    }
                })
            ;

            $products[] = $product;
        }

        if (!empty($products)) {
            $this->processProducts($products);
        }
    }

    private function processProducts(array $products): void
    {
        foreach ($products as $product) {
            if ($productObj = $this->productRepository->findOneBy(['name' => $product['name']])) {
                $this->processVariations($productObj, $product['offers']);

                continue;
            }

            $productObj = $this->productService->create($product)->save();
            if ($productObj instanceof Product) {
                printf("Added %s...\n", $productObj->getName());
                $this->processVariations($productObj, $product['offers']);
            }
        }
    }

    private function processVariations(Product $product, array $variations): void
    {
        printf("Checking %s variations...\n", $product->getName());
        foreach ($variations as $variation) {
            if ($variationObj = $this->variationRepository->findOneBy(['url' => $variation['url']])) {
                $this->processPrice($variationObj, $variation['price']);

                continue;
            }

            if (u($variation['availability'])->match('/(?:OutOfStock)/')) {
                /** New variation, but not in stock YET so don't create a new variation. */
                continue;
            }

            $variationObj = $this->variationService->setProduct($product)->create($variation)->save();
            if ($variationObj instanceof Variation) {
                printf("Added %s...\n", $variationObj->getName());
                $this->processPrice($variationObj, $variation['price']);
            }
        }
    }

    private function processPrice(Variation $variation, string $price): void
    {
        $dateToday = new \DateTime('now', new \DateTimeZone('Europe/Amsterdam'));

        if (!empty($variation->getPrices()->first())) {
            $lastVariationPriceDateFormat = $variation->getPrices()->last()->getDate()->format('Y-m-d');
            if ((date('Y-m-d') === $lastVariationPriceDateFormat) && (int) ($price * 100) === $variation->getPrices()->last()->getPrice()) {
                return;
            }
        }

        $priceObj = $this->priceService->setVariation($variation)->create(['price' => (int) ($price * 100), 'date' => $dateToday])->save();
        if ($priceObj instanceof Price) {
            printf("Added a price (%s) to %s...\n", $priceObj->getPrice(), $variation->getName());
        }
    }
}
