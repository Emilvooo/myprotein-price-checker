<?php

namespace App\Service;

use App\DTO\ProductWithUpdateDate;

class ProductTransformer
{
    public function transformProducts($products, $lastUpdatedVariations): array
    {
        $productsWithUpdateDate = [];
        foreach ($products as $product) {
            $productWithUpdateDate = new ProductWithUpdateDate();
            $productWithUpdateDate->setProduct($product);
            foreach ($lastUpdatedVariations as $lastUpdatedVariation) {
                if ($product->getName() !== $lastUpdatedVariation['name']) {
                    continue;
                }

                $productWithUpdateDate->setUpdateDate(new \DateTime($lastUpdatedVariation['date']));
            }

            $productsWithUpdateDate[] = $productWithUpdateDate;
        }

        return $productsWithUpdateDate;
    }
}