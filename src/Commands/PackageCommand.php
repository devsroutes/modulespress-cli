<?php

namespace ModulesPressCLI\Commands;

use ModulesPressCLI\Services\PluginPackagingService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PackageCommand extends Command
{
    protected static $defaultName = 'pack';

    protected function configure()
    {
        $this
            ->setDescription('Package a production ready ModulesPress based plugin.')
            ->setHelp('This command allows you to package a production ready ModulesPress based plugin.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Packaging Plugin');
        $pluginPackagingService = new PluginPackagingService($input, $output, $input->getOption('verbose'), $io);
        $pluginPackagingService->package();
        return Command::SUCCESS;
    }
}
