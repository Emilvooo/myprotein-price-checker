<?php

namespace App\Command;

use App\Scraper\ScraperService;
use App\Service\WebScraperService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScrapeProductsCommand extends Command
{
    protected static $defaultName = 'scrape-products';

    private $webScraperService;
    /**
     * @var ScraperService
     */
    private $scraperService;

    public function __construct(WebScraperService $webScraperService, ScraperService $scraperService)
    {
        $this->webScraperService = $webScraperService;
        $this->scraperService = $scraperService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Adding products')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->scraperService->startScraping();
        //$this->webScraperService->processData();
        $output->writeln('Scraping done!');
    }
}
