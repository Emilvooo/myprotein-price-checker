<?php

namespace App\Controller;

use App\Entity\ScrapeableProduct;
use App\Repository\ProductRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function index(Request $request, ProductRepository $products)
    {
        $products = $products->getMostRecentProductsToday();

        return $this->render('dashboard/index.html.twig',
            ['products' => $products]
        );
    }

    /**
     * @Route("/dashboard/item", name="dashboard_item")
     */
    public function item()
    {

    }

    /**
     * @Route("/dashboard/scrapable/add", name="dashboard_scrapable_item_add")
     */
    public function addScrapableProduct(Request $request)
    {
        $scrapableProduct = new ScrapeableProduct();

        $form = $this->createFormBuilder($scrapableProduct)
            ->add('url', TextType::class)
            ->add('submit', SubmitType::class, ['label' => 'Submit'])
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

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/scrapable/add.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
