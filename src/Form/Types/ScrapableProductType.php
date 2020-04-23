<?php

declare(strict_types=1);

namespace App\Form\Types;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ScrapableProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', TextType::class, ['label' => 'URL'])
            ->add('submit', SubmitType::class, ['label' => 'Submit', 'attr' => ['class' => 'btn-dark']])
        ;
    }
}
