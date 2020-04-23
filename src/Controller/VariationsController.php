<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Variation;
use App\Repository\VariationRepository;
use App\Service\GoogleChartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VariationsController extends AbstractController
{
    /** @var VariationRepository */
    private $variationRepository;
    /** @var GoogleChartService */
    private $googleChartService;

    public function __construct(
        VariationRepository $variationRepository,
        GoogleChartService $googleChartService
    ) {
        $this->variationRepository = $variationRepository;
        $this->googleChartService = $googleChartService;
    }

    public function item(Product $product, string $variationSlug)
    {
        $variation = $this->variationRepository->findOneBy([
            'product' => $product->getId(),
            'slug' => $variationSlug,
        ]);

        if (!$variation instanceof Variation) {
            return $this->redirectToRoute('products_item', [
                'slug' => $product->getSlug(),
            ]);
        }

        $lineChart = $this->googleChartService->createLineChart($variation);

        $response = $this->render(
            'variations/item.html.twig',
            [
                'variation' => $variation,
                'product' => $product,
                'lineChart' => $lineChart,
            ]
        );

        $response->setSharedMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }
}
