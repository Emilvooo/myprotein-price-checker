<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Entity\Variation;
use App\Form\ScrapableProductType;
use App\Repository\ProductRepository;
use App\Repository\VariationRepository;
use App\Service\FormHandlerService;
use App\Service\GoogleChartService;
use App\Service\ProductsTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    /**
     * @Route("/", name="products_index")
     *
     * @param ProductRepository $productRepository
     * @param ProductsTransformer $productsTransformer
     *
     * @return Response
     */
    public function index(ProductRepository $productRepository, ProductsTransformer $productsTransformer): Response
    {
        $products = $productRepository->findAll();

        $lastUpdatedVariations = $productRepository->getLastUpdatedVariations();
        $productsWithUpdateDate = $productsTransformer->transformProductsIntoDto($products, $lastUpdatedVariations);

        return $this->render(
            'products/index.html.twig',
            [
                'products' => $productsWithUpdateDate,
            ]
        );
    }

    /**
     * @Route("/variations/{slug}/{variation?}", name="products_variation_index")
     *
     * @param Product $product
     * @param VariationRepository $variationRepository
     * @param GoogleChartService $googleChartService
     * @param Variation|null $variation
     *
     * @return Response
     */
    public function item(Product $product, VariationRepository $variationRepository, GoogleChartService $googleChartService, $variation): Response
    {
        if (null !== $variation) {
            $variation = $variationRepository->findOneBy(['product' => $product->getId(), 'slug' => $variation]);
            if (!$variation instanceof Variation) {
                return $this->render(
                    'products/item.html.twig',
                    ['product' => $product]
                );
            }

            $lineChart = $googleChartService->createLineChart($variation);

            return $this->render(
                'variation/item.html.twig',
                [
                    'variation' => $variation,
                    'product' => $variation->getProduct(),
                    'linechart' => $lineChart,
                ]
            );
        }

        $variations = $variationRepository->getVariations($product);

        return $this->render(
            'products/item.html.twig',
            [
                'product' => $product,
                'variations' => $variations,
            ]
        );
    }

    /**
     * @Route("/products/add", name="products_add")
     *
     * @param Request $request
     * @param FormHandlerService $formHandlerService
     *
     * @return RedirectResponse|Response
     */
    public function addProduct(Request $request, FormHandlerService $formHandlerService)
    {
        $scrapableProduct = new ScrapeableProduct();
        $form = $this->createForm(ScrapableProductType::class, $scrapableProduct);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formHandler = $formHandlerService->formHandler($form);
            if ($formHandler) {
                $this->addFlash(
                    'success',
                    'Your changes were saved!'
                );

                return $this->redirectToRoute('products_index');
            }

            $this->addFlash(
                'danger',
                'Your changes were not saved!'
            );
        }

        return $this->render('products/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
