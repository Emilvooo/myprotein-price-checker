<?php

declare(strict_types=1);

namespace App\Command;

use App\Scraper\ScraperService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScrapeProductsCommand extends Command
{
    protected static $defaultName = 'scrape-products';

    /** @var ScraperService */
    private $scraperService;

    public function __construct(ScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
        parent::__construct();
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
        $this->scraperService->startScraping();
        $output->writeln('Scraping done!');

        return 0;
    }
}
