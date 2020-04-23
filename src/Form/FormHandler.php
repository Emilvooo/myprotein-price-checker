<?php

declare(strict_types=1);

namespace App\Form;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

class FormHandler implements FormHandlerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(FormInterface $form): bool
    {
        $this->entityManager->persist($form->getData());
        $this->entityManager->flush();

        return true;
    }
}
