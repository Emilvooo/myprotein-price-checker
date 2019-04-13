<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

class FormHandlerService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function formHandler(FormInterface $form)
    {
        if (!strpos($form->getData()->getUrl(), 'myprotein.com')) {
            return false;
        }

        $this->entityManager->persist($form->getData());
        $this->entityManager->flush();

        return true;
    }
}