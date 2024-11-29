<?php

namespace ModulesPressCLI\Commands;

use ModulesPressCLI\Services\PluginCreationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NewCommand extends Command
{
    protected static $defaultName = 'new';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new ModulesPress plugin.')
            ->setHelp('This command allows you to create a new plugin by copying files, installing depedencies and configuring namespaces...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🚀 ModulesPress Plugin Generator');
        $pluginCreationService = new PluginCreationService($input, $output, $io);
        $pluginCreationService->createNew();
        return Command::SUCCESS;
    }
}
