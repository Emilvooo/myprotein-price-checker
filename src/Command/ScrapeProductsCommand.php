<?php

namespace App\Command;

use App\Service\WebScraperService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\GenericEvent;

class ScrapeProductsCommand extends Command
{
    protected static $defaultName = 'scrape-products';

    private $webScraperService;

    public function __construct(WebScraperService $webScraperService)
    {
        $this->webScraperService = $webScraperService;

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
        $this->webScraperService->processData();
        $output->writeln('Products successfully added!');
    }
}
