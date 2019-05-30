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
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

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
        EngineInterface $templating,
        \Swift_Mailer $mailer
    )
    {
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
        foreach ($scrapableProducts as $product) {
            $crawler = $this->client->request('GET', $product->getUrl());

            $product = json_decode($crawler->filterXpath('//script[@type="application/ld+json"]')->text(), true);
            $variations = json_decode($crawler->filterXpath('//script[@type="application/ld+json"]')->text(), true)['offers'];

            $product = $this->addProduct($product);
            printf("Checking %s...\n", $product->getName());
            if ($product !== null) {
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
        if (empty($variations) || $product === null) {
            return;
        }

        $existingVariations = $this->variationRepository->findBy(['product' => $product->getId()]);

        foreach ($variations as $variation) {
            if ($variationObj = $this->variationRepository->findOneBy(['url' => $variation['url']])) {
                $this->addPrice($variationObj, $variation['price']);
                continue;
            }

            $crawler = $this->client->request('GET', 'https://nl.myprotein.com/' . $variation['sku'] . '.images?variation=false&stringTemplatePath=components/athenaProductImageCarousel/athenaProductImageCarousel');

            $variationObj = new Variation();
            $variationObj->setName(str_replace(['New -', 'New –', $product->getName() . ' - '], '', $crawler->filter('.athenaProductImageCarousel_thumbnail')->attr('alt')));
            $variationObj->setUrl($variation['url']);
            $variationObj->setSlug(str_replace([' '], '', strtolower($variationObj->getName())));
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
        $dateTodayFormat = $dateToday->format('Y-m-d');

        if (!empty($variation->getPrices()->first())) {
            $lastVariationPriceDateFormat = $variation->getPrices()->last()->getDate()->format('Y-m-d');

            $dateDifference = $dateToday->diff($variation->getPrices()->last()->getDate());
            if (substr($dateDifference->format('%r%a'), 1) >= 2) {
                $this->sendMail($variation, '', 'back_in_stock');
            }

            if ($dateTodayFormat === $lastVariationPriceDateFormat) {
                if ((int)($variationPrice * 100) === $variation->getPrices()->last()->getPrice()) {
                    return;
                }

                $this->sendMail($variation, $variationPrice, 'price_changed');
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
        $subject = $variation->getProduct()->getName() . ' - ' . $variation->getName() . ' is back in stock!';
        $mailVars = ['variation' => $variation];
        if ($template === 'price_changed') {
            $subject = 'Price of product ' . $variation->getProduct()->getName() . ' - ' . $variation->getName() . ' changed!';
            $mailVars['newPrice'] = $variationPrice;
        }

        $message = (new \Swift_Message($subject))
            ->setFrom('info@myprotein-price-checker.com')
            ->setTo('emilveldhuizen@gmail.com')
            ->setBody(
                $this->templating->render(
                    'emails/' . $template . '.html.twig',
                    $mailVars
                ),
                'text/html'
            );

        $numSent = $this->mailer->send($message, $errors);
        printf("Sent %d message\n", $numSent);
    }
}