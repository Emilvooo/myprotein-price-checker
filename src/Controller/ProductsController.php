<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Repository\ProductRepository;
use App\Repository\VariationRepository;
use App\Service\GoogleChartService;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    /**
     * @Route("/", name="products_index")
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function index(ProductRepository $productRepository)
    {
        $products = $productRepository->findAll();

        return $this->render('products/index.html.twig',
            ['products' => $products]
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
            $variation = $variationRepository->findOneBy(['slug' => $variation]);
            $lineChart = $googleChartService->createLineChart($variation);

            return $this->render('variation/item.html.twig',
                [
                    'variation' => $variation,
                    'product' => $variation->getProduct(),
                    'linechart' => $lineChart
                ]
            );
        }

        return $this->render('products/item.html.twig',
            ['product' => $product,]
        );
    }

    /**
     * @Route("/products/add", name="products_add")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function addProduct(Request $request)
    {
        $scrapableProduct = new ScrapeableProduct();

        $form = $this->createFormBuilder($scrapableProduct)
            ->add('url', TextType::class,
                [
                    'label' => 'URL',
                ]
            )
            ->add('submit', SubmitType::class,
                [
                    'label' => 'Submit',
                    'attr' => ['class' => 'btn-dark']
                ]
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $scrapableProduct = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($scrapableProduct);
            $entityManager->flush();
            $this->addFlash(
                'notice',
                'Your changes were saved!'
            );

            return $this->redirectToRoute('products_index');
        }

        return $this->render('products/add.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
