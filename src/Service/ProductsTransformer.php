<?php

namespace App\Service;

use App\DTO\ProductWithUpdateDate;
use App\Entity\Product;

class ProductsTransformer
{
    private $productsWithUpdateDate = [];

    public function transformProductsIntoDto($products, $lastUpdatedVariations): array
    {
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

            $this->productsWithUpdateDate[] = $productWithUpdateDate;
        }

        usort($this->productsWithUpdateDate, function($a, $b) {
            return $b->getUpdateDate() <=> $a->getUpdateDate();
        });

        return $this->productsWithUpdateDate;
    }

    public function mostRecentDate(): string
    {
        $mostRecent = '';

        $updateDates = [];
        /** @var ProductWithUpdateDate $productWithUpdateDate */
        foreach ($this->productsWithUpdateDate as $productWithUpdateDate) {
            $updateDates[] = $productWithUpdateDate->getUpdateDate()->format('Y-m-d H');
        }

        $allDatesAreTheSame = (count(array_unique($updateDates)) === 1);
        if (!$allDatesAreTheSame) {
            foreach ($updateDates as $updateDate) {
                if ($updateDate > $mostRecent) {
                    $mostRecent = $updateDate;
                }
            }
        }

        return $mostRecent;
    }
}