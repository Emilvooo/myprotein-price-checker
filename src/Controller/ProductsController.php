<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Entity\Variation;
use App\Repository\ProductRepository;
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
     * @Route("/variations/{slug}", name="products_item")
     * @param Product $product
     * @return Response
     */
    public function item(Product $product)
    {
        return $this->render('products/item.html.twig',
            ['product' => $product,]
        );
    }

    /**
     * @Route("/products/{slug}", name="product_detail")
     * @param Variation $variation
     * @param GoogleChartService $googleChartService
     * @return Response
     */
    public function detail(Variation $variation, GoogleChartService $googleChartService)
    {
        $lineChart = $googleChartService->createLineChart($variation);

        return $this->render('products/variation/item.html.twig',
            [
                'variation' => $variation,
                'product' => $variation->getProduct(),
                'linechart' => $lineChart
            ]
        );
    }

    /**
     * @Route("/scrapable/add", name="scrapable_product_add")
     */
    public function addScrapableProduct(Request $request)
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
        return $this->render('scrapable/add.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
