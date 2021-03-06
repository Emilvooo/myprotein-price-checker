<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Price;
use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Entity\Variation;
use App\Repository\ProductRepository;
use App\Repository\VariationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Goutte\Client;
use Twig\Environment;

class WebScraperService
{
    private $client;
    private $entityManager;
    private $productRepository;
    private $variationRepository;
    private $mailer;
    private $templating;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        VariationRepository $variationRepository,
        Environment $templating,
        \Swift_Mailer $mailer
    ) {
        $this->client = new Client();
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->variationRepository = $variationRepository;
        $this->templating = $templating;
        $this->mailer = $mailer;
    }

    public function processData(): void
    {
        $scrapableProducts = $this->entityManager->getRepository(ScrapeableProduct::class)->findAll();
        shuffle($scrapableProducts);

        foreach ($scrapableProducts as $product) {
            $crawler = $this->client->request('GET', $product->getUrl());

            $product = json_decode($crawler->filterXpath('//script[@type="application/ld+json"]')->text(), true);
            $variations = json_decode($crawler->filterXpath('//script[@type="application/ld+json"]')->text(), true)['offers'];

            $product = $this->addProduct($product);
            printf("Checking %s...\n", $product->getName());
            if (null !== $product) {
                $this->addVariations($variations, $product);
            }
        }
    }

    public function addProduct($product): ?Product
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

        printf("Added %s...\n", $productObj->getName());

        return $productObj;
    }

    public function addVariations($variations, Product $product): void
    {
        if (empty($variations) || null === $product) {
            return;
        }

        foreach ($variations as $variation) {
            if ($variationObj = $this->variationRepository->findOneBy(['url' => $variation['url']])) {
                if (0 === $variationObj->getInStock() && 'https://schema.org/InStock' === $variation['availability']) {
                    //$this->sendMail($variationObj, '', 'back_in_stock');
                }

                $variationObj->setInStock('https://schema.org/InStock' !== $variation['availability'] ? 0 : 1);

                $this->entityManager->persist($variationObj);
                $this->entityManager->flush();

                $this->addPrice($variationObj, $variation['price']);

                continue;
            }

            /** new variation but not in stock yet - price could be strange so dont add it yet. (chart and history would look strange **/
            if ('https://schema.org/InStock' !== $variation['availability']) {
                continue;
            }

            $crawler = $this->client->request('GET', 'https://nl.myprotein.com/'.$variation['sku'].'.images?variation=false&stringTemplatePath=components/athenaProductImageCarousel/athenaProductImageCarousel');

            $variationObj = new Variation();
            $variationObj->setName(str_replace(['New -', 'New –', $product->getName().' - '], '', $crawler->filter('.athenaProductImageCarousel_thumbnail')->attr('alt')));
            $variationObj->setUrl($variation['url']);
            $variationObj->setSlug(str_replace([' '], '', strtolower($variationObj->getName())));
            $variationObj->setInStock(1);
            $variationObj->setProduct($product);

            $this->entityManager->persist($variationObj);
            $this->entityManager->flush();

            printf("Added %s...\n", $variationObj->getName());

            if ($variationObj->getId()) {
                $this->addPrice($variationObj, $variation['price']);
            }
        }
    }

    public function addPrice(Variation $variation, $variationPrice): void
    {
        $dateToday = new \DateTime('now', new \DateTimeZone('Europe/Amsterdam'));

        if (!empty($variation->getPrices()->first())) {
            $lastVariationPriceDateFormat = $variation->getPrices()->last()->getDate()->format('Y-m-d');
            if (date('Y-m-d') === $lastVariationPriceDateFormat) {
                if ((int) ($variationPrice * 100) === $variation->getPrices()->last()->getPrice()) {
                    return;
                }
            }
        }

        $priceObj = new Price();
        $priceObj->setPrice($variationPrice * 100);
        $priceObj->setDate($dateToday);
        $priceObj->setVariation($variation);

        $this->entityManager->persist($priceObj);
        $this->entityManager->flush();

        printf("Added a price to %s...\n", $variation->getName());
    }

    public function sendMail(Variation $variation, $variationPrice, $template): void
    {
        $subject = $variation->getProduct()->getName().' - '.$variation->getName().' is back in stock!';
        $mailVars = ['variation' => $variation];
        if ('price_changed' === $template) {
            $subject = 'Price of product '.$variation->getProduct()->getName().' - '.$variation->getName().' changed!';
            $mailVars['newPrice'] = $variationPrice;
        }

        $message = (new \Swift_Message($subject))
            ->setFrom('info@myprotein-price-checker.com')
            ->setTo('emilveldhuizen@gmail.com')
            ->setBody(
                $this->templating->render(
                    'emails/'.$template.'.html.twig',
                    $mailVars
                ),
                'text/html'
            )
        ;

        $numSent = $this->mailer->send($message, $errors);

        printf("Sent %d message\n", $numSent);
    }
}
