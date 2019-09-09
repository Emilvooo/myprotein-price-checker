<?php

namespace App\Scraper;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\ScrapeableProductRepository;
use App\Service\Entity\ProductService;
use Goutte\Client;

class ScraperService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ScrapeableProductRepository
     */
    private $scrapeableProductRepository;
    /**
     * @var ProductService
     */
    private $productService;

    public function __construct(ProductRepository $productRepository, ScrapeableProductRepository $scrapeableProductRepository, ProductService $productService)
    {
        $this->client = new Client();
        $this->productRepository = $productRepository;
        $this->scrapeableProductRepository = $scrapeableProductRepository;
        $this->productService = $productService;
    }

    public function startScraping(): void
    {
        $scrapeableProducts = $this->scrapeableProductRepository->findAll();
        foreach ($scrapeableProducts as $scrapeableProduct) {
            $crawler = $this->client->request('GET', $scrapeableProduct->getUrl());
            $crawler->filterXPath('//*[@type="application/ld+json"]')
                ->each(static function ($node) use (&$product) {
                    $data = json_decode($node->text(), true);
                    if (array_key_exists('offers', $data)) {
                        $product = $data;
                    }
                });

            $products[] = $product;
        }

        if (!empty($products)) {
            $this->processProducts($products);
        }
    }

    private function processProducts(array $products): void
    {
        foreach ($products as $product) {
//            if ($productObj = $this->productRepository->findOneBy(['name' => $product['name']])) {
//                $this->processVariations($productObj, $product['offers']);
//                continue;
//            }

            $this->productService->create($product)->save();
        }
    }

    private function processVariations(Product $product, array $variations)
    {

    }
}