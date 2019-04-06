<?php
namespace App\Service;

use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use Goutte\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class WebScraperService
{
    private $client;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->client = new Client();
        $this->entityManager = $entityManager;
    }

    public function processData()
    {
        $products = $this->entityManager->getRepository(ScrapeableProduct::class)->findAll();
        foreach ($products as $product) {
            $crawler = $this->client->request('GET', $product->getUrl());
            $productVariations = json_decode($crawler->filterXpath('//script[@type="application/ld+json"]')->text(), true);
            foreach ($productVariations['offers'] as $productVariation) {
                $crawler = $this->client->request('GET', $productVariation['url']);
                $productVariation['name'] = $crawler->filter('.athenaProductImageCarousel_image')->attr('alt');;
                if (!empty($productVariation)) {
                    $this->saveProduct($productVariation);
                }
            }
        }
    }

    public function saveProduct($productVariation)
    {
        $date = new \DateTime('now');

        $product = new Product();
        $product->setName($productVariation['name']);
        $product->setPrice($productVariation['price'] * 100);
        $product->setDate($date);
        $product->setUrl($productVariation['url']);

        $this->entityManager->persist($product);

        $this->entityManager->flush();
    }
}