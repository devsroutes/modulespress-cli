<?php

namespace ModulesPressCLI\Commands;

use ModulesPressCLI\Services\PluginExtractionService;
use ModulesPressCLI\Services\CommonService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeProviderCommand extends Command
{
    protected static $defaultName = 'make:provider';
    private PluginExtractionService $pluginExtractionService;
    private CommonService $commonService;

    public function __construct()
    {
        parent::__construct();
        $this->pluginExtractionService = new PluginExtractionService();
        $this->commonService = new CommonService();
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates a new provider file.')
            ->setHelp('This command allows you to create a new provider file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the provider')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the provider'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Provider Generator');

        $providerName = $input->getArgument('name');
        $createDir = $input->getOption('dir');

        try {
            $this->createProviderFile($providerName, $createDir, $io);
            $io->success("Provider '{$providerName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createProviderFile(string $providerName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Services" : '');

        $providerTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use ModulesPress\\Foundation\\DI\\Attributes\\Injectable;

#[Injectable]
class {$providerName} {
    public function __construct() {}
}
PHP;

        if ($createDir) {
            $providerDir = "Services";
            if (!is_dir($providerDir)) {
                mkdir($providerDir, 0777, true);
            }
            $providerFile = $providerDir . "/{$providerName}.php";
        } else {
            $providerFile = "{$providerName}.php";
        }

        if (file_exists($providerFile)) {
            throw new \Exception("Provider '{$providerName}' already exists!");
        }

        file_put_contents($providerFile, $providerTemplate);
        $io->note("Created provider in namespace: " . $completeNamespace);
    }
}
