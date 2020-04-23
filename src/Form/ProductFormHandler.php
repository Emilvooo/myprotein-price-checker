<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ScrapeableProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

class ProductFormHandler implements FormHandlerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(FormInterface $form): bool
    {
        /** @var ScrapeableProduct $formData */
        $formData = $form->getData();

        $productUrl = $formData->getUrl();
        if (!strpos($productUrl, 'myprotein.com')) {
            return false;
        }

        $this->entityManager->persist($form->getData());
        $this->entityManager->flush();

        return true;
    }
}
