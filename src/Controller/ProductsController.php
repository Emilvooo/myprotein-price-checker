<?php

namespace App\Controller;

use App\DTO\ProductWithUpdateDate;
use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Form\ScrapableProductType;
use App\Repository\ProductRepository;
use App\Repository\VariationRepository;
use App\Service\FormHandlerService;
use App\Service\GoogleChartService;
use App\Service\ProductTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    /**
     * @Route("/", name="products_index")
     * @param ProductRepository $productRepository
     * @param ProductTransformer $productTransformer
     * @return Response
     */
    public function index(ProductRepository $productRepository, ProductTransformer $productTransformer)
    {
        $products = $productRepository->findAll();
        $lastUpdatedVariations = $productRepository->getLastUpdatedVariations();
        $productsWithUpdateDate = $productTransformer->transformProducts($products, $lastUpdatedVariations);

        return $this->render('products/index.html.twig',
            [
                'products' => $productsWithUpdateDate,
            ]
        );
    }

    /**
     * @Route("/variations/{slug}/{variation?}", name="products_variation_index")
     * @param Product $product
     * @param VariationRepository $variationRepository
     * @param GoogleChartService $googleChartService
     * @param string $variation
     * @return Response
     */
    public function item(Product $product, VariationRepository $variationRepository, GoogleChartService $googleChartService, $variation)
    {
        if (!empty($variation)) {
            $variation = $variationRepository->findOneBy(['product' => $product->getId(), 'slug' => $variation]);
            $lineChart = $googleChartService->createLineChart($variation);

            return $this->render('variation/item.html.twig',
                [
                    'variation' => $variation,
                    'product' => $variation->getProduct(),
                    'linechart' => $lineChart
                ]
            );
        };

        return $this->render('products/item.html.twig',
            ['product' => $product,]
        );
    }

    /**
     * @Route("/products/add", name="products_add")
     * @param Request $request
     * @param FormHandlerService $formHandlerService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
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
            'form' => $form->createView()
        ]);
    }
}
