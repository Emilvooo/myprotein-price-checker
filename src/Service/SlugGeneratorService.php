<?php

declare(strict_types=1);

namespace App\Service;

class SlugGeneratorService
{
    public function generateSlug($value)
    {
        return str_replace(' ', '', strtolower($value));
    }
}
