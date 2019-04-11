<?php
namespace App\Service;

use App\Entity\Price;
use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Entity\Variation;
use App\Repository\ProductRepository;
use App\Repository\VariationRepository;
use Goutte\Client;
use Doctrine\ORM\EntityManagerInterface;

class WebScraperService
{
    private $client;
    private $entityManager;
    private $productRepository;
    private $variationRepository;
    private $mailer;

    public function __construct(EntityManagerInterface $entityManager, ProductRepository $productRepository, VariationRepository $variationRepository ,\Swift_Mailer $mailer)
    {
        $this->client = new Client();
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->variationRepository = $variationRepository;
        $this->mailer = $mailer;
    }

    public function processData()
    {
        $scrapableProducts = $this->entityManager->getRepository(ScrapeableProduct::class)->findAll();
        foreach ($scrapableProducts as $product) {
            $crawler = $this->client->request('GET', $product->getUrl());

            $product = json_decode($crawler->filterXpath('//script[@type="application/ld+json"]')->text(), true);
            $variations = json_decode($crawler->filterXpath('//script[@type="application/ld+json"]')->text(), true)['offers'];

            $product = $this->addProduct($product);
            if (!empty($product)) {
                $this->addVariations($variations, $product);
            }
        }
    }

    public function addProduct($product)
    {
        if (empty($product)) {
            return null;
        }

        if ($productObj = $this->productRepository->findOneBy(['name' => $product['name']])) {
            return $productObj;
        }

        $productObj = new Product();
        $productObj->setName($product['name']);
        $productObj->setDescription($product['description']);
        $productObj->setSlug(str_replace(' ', '', strtolower($productObj->getName())));

        $this->entityManager->persist($productObj);
        $this->entityManager->flush();

        return $productObj;
    }

    public function addVariations($variations, Product $product)
    {
        if (empty($variations) || is_null($product)) {
            return null;
        }

        foreach ($variations as $variation) {
            if ($variationObj = $this->variationRepository->findOneBy(['url' => $variation['url']])) {
                $this->addPrice($variationObj, $variation['price']);
                continue;
            }

            $crawler = $this->client->request('GET', 'https://nl.myprotein.com/' . $variation['sku'] . '.images?variation=false&stringTemplatePath=components/athenaProductImageCarousel/athenaProductImageCarousel');

            $variationObj = new Variation();
            $variationObj->setName(str_replace(['New -', 'New –'], '', $crawler->filter('.athenaProductImageCarousel_thumbnail')->attr('alt')));
            $variationObj->setUrl($variation['url']);
            $variationObj->setSlug(str_replace(' ', '', strtolower($variationObj->getName())));
            $variationObj->setProduct($product);

            $this->entityManager->persist($variationObj);
            $this->entityManager->flush();

            if ($variationObj->getId()) {
                $this->addPrice($variationObj, $variation['price']);
            }
        }
    }

    public function addPrice(Variation $variation, $variationPrice)
    {
        $dateToday = new \DateTime('now', new \DateTimeZone('Europe/Amsterdam'));
        if (!empty($variation->getPrices()->first())) {
            if ($dateToday->format('Y-m-d') == $variation->getPrices()->last()->getDate()->format('Y-m-d')) {
                if (intval($variationPrice * 100) == $variation->getPrices()->last()->getPrice()) {
                    return;
                }

                $this->sendMail($variation, $variationPrice);
            }
        }

        $priceObj = new Price();
        $priceObj->setPrice($variationPrice * 100);
        $priceObj->setDate($dateToday);
        $priceObj->setVariation($variation);

        $this->entityManager->persist($priceObj);
        $this->entityManager->flush();
    }

    public function sendMail(Variation $variation, $variationPrice)
    {
        $message = (new \Swift_Message('Price of product ' . $variation->getName() . ' changed!'))
            ->setFrom('test@myprotein-price-checker.com')
            ->setTo('emilveldhuizen@gmail.com')
            ->setBody(
                'The price of this product is now €' . $variationPrice . ' with 35% discount!'
            );

        $numSent = $this->mailer->send($message, $errors);
        printf("Sent %d messages\n", $numSent);
    }
}