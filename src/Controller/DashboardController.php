<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ScrapeableProduct;
use App\Repository\ProductRepository;
use App\Service\GoogleChartService;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="product_overview")
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function index(ProductRepository $productRepository)
    {
        $products = $productRepository->findAll();

        return $this->render('dashboard/index.html.twig',
            ['products' => $products]
        );
    }

    /**
     * @Route("/products/{slug}", name="product_show")
     *
     */
    public function item(Product $product, GoogleChartService $googleChartService)
    {
        $lineChart = $googleChartService->createLineChart($product);

        return $this->render('dashboard/item.html.twig',
            [
                'product' => $product,
                'prices' => $product->getPrices(),
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

            return $this->redirectToRoute('dashboard_index');
        }

        return $this->render('dashboard/scrapable/add.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
