<?php
namespace App\Service;

use App\Entity\Product;

class GoogleChartService
{
    public function createLineChart(Product $product)
    {
        $lineChart = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\Material\LineChart();

        $priceHistory[] = ['Date', 'Price (35% discount included)'];
        foreach ($product->getPrices() as $key => $price) {
            $priceHistory[$key + 1] = [$price->getDate()->format('Y-m-d'), $price->getPrice() / 100 * 0.65];
        }

        $lineChart->getData()->setArrayToDataTable(
            $priceHistory
        );

        $lineChart->getOptions()->setTitle($product->getName());
        $lineChart->getOptions()->setCurveType('function');
        $lineChart->getOptions()->setHeight(500);
        $lineChart->getOptions()->setWidth(1100);

        return $lineChart;
    }
}
