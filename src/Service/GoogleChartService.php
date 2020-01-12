<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Variation;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\Material\LineChart;

class GoogleChartService
{
    public function createLineChart(Variation $variation): LineChart
    {
        $lineChart = new LineChart();

        $priceHistory[] = ['Date', 'Price'];
        foreach ($variation->getPrices() as $key => $price) {
            $priceHistory[$key + 1] = [$price->getDate()->format('Y-m-d'), $price->getPrice() / 100];
        }

        $lineChart->getData()->setArrayToDataTable(
            $priceHistory
        );

        $lineChart->getOptions()->setTitle($variation->getName());
        $lineChart->getOptions()->setCurveType('function');
        $lineChart->getOptions()->setHeight('auto');
        $lineChart->getOptions()->setWidth('auto');

        return $lineChart;
    }
}
