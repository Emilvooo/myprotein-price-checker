<?php
namespace App\Service;

use App\Entity\Price;
use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Repository\ProductRepository;
use Goutte\Client;
use Doctrine\ORM\EntityManagerInterface;

class WebScraperService
{
    private $client;
    private $entityManager;
    private $productRepository;

    public function __construct(EntityManagerInterface $entityManager, ProductRepository $productRepository)
    {
        $this->client = new Client();
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
    }

    public function processData()
    {
        $products = $this->entityManager->getRepository(ScrapeableProduct::class)->findAll();
        foreach ($products as $product) {
            $crawler = $this->client->request('GET', $product->getUrl());
            $productVariations = json_decode($crawler->filterXpath('//script[contains(., "offers")]')->text(), true)['offers'];
            foreach ($productVariations as $productVariation) {
                $crawler = $this->client->request('GET', 'https://nl.myprotein.com/'.$productVariation['sku'].'.images?variation=false&stringTemplatePath=components/athenaProductImageCarousel/athenaProductImageCarousel');
                $productVariation['name'] = $crawler->filter('.athenaProductImageCarousel_thumbnail')->attr('alt');
                if (!empty($productVariation)) {
                    $product = $this->addProduct($productVariation);
                    $this->setPrice($product, $productVariation);
                }
            }
        }
    }

    public function addProduct($productVariation)
    {
        $products = $this->productRepository->findAll();
        if (!empty($products)) {
            foreach ($products as $product) {
                if ($product->getName() === $productVariation['name']) {
                    return $product;
                }
            }
        }

        $product = new Product();
        $product->setName($productVariation['name']);
        $product->setUrl($productVariation['url']);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $product = $this->productRepository->findOneBy(['name' => $productVariation['name']]);

        return $product;
    }

    public function setPrice(Product $product, $productVariation)
    {
        $dateToday = new \DateTime('now');
        if (!empty($product->getPrices()->last())) {
            if ($dateToday->format('Y-m-d') == $product->getPrices()->last()->getDate()->format('Y-m-d')) {
                if (intval($productVariation['price'] * 100) == $product->getPrices()->last()->getPrice()) {
                    return;
                }
            }
        }


        $price = new Price();
        $price->setPrice($productVariation['price'] * 100);
        $price->setDate(new \DateTime('now'));
        $price->setProduct($product);

        $this->entityManager->persist($price);
        $this->entityManager->flush();
    }
}