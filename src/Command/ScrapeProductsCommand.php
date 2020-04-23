<?php

declare(strict_types=1);

namespace App\Command;

use App\Scraper\ProductsScraper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScrapeProductsCommand extends Command
{
    protected static $defaultName = 'scrape-products';

    /** @var ProductsScraper */
    private $productsScraper;

    public function __construct(ProductsScraper $productsScraper)
    {
        parent::__construct();
        $this->productsScraper = $productsScraper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Adding products')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting...');
        $this->productsScraper->startScraping();
        $output->writeln('Scraping done!');

        return 0;
    }
}
