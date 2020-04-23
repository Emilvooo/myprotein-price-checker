<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\FormInterface;

interface FormHandlerInterface
{
    public function handle(FormInterface $form);
}
