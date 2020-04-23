<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Form\FormHandlerInterface;
use App\Form\Types\ScrapableProductType;
use App\Repository\ProductRepository;
use App\Repository\VariationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductsController extends AbstractController
{
    /** @var ProductRepository */
    private $productRepository;
    /** @var VariationRepository */
    private $variationRepository;
    /** @var FormHandlerInterface */
    private $formHandler;

    public function __construct(
        ProductRepository $productRepository,
        VariationRepository $variationRepository,
        FormHandlerInterface $formHandler
    ) {
        $this->productRepository = $productRepository;
        $this->variationRepository = $variationRepository;
        $this->formHandler = $formHandler;
    }

    public function index(): Response
    {
        $products = $this->productRepository->findBy([], [
            'updated' => 'DESC',
        ]);

        $response = $this->render(
            'products/index.html.twig',
            [
                'products' => $products,
            ]
        );

        $response->setSharedMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    public function item(Product $product): Response
    {
        $variations = $this->variationRepository->getVariations($product);

        $response = $this->render(
            'products/item.html.twig',
            [
                'product' => $product,
                'variations' => $variations,
            ]
        );

        $response->setSharedMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    public function newProduct(Request $request)
    {
        $form = $this->createForm(ScrapableProductType::class, new ScrapeableProduct());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formSuccess = $this->formHandler->handle($form);
            if ($formSuccess) {
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
