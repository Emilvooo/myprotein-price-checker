<?php

namespace App\Service;

use App\DTO\ProductWithUpdateDate;
use App\Entity\Product;

class ProductsTransformer
{
    public function transformProductsIntoDto($products, $lastUpdatedVariations): array
    {
        $productsWithUpdateDate = [];
        /** @var Product $product */
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