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

class MakeCheckCommand extends Command
{
    protected static $defaultName = 'make:check';
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
            ->setDescription('Creates a new check file.')
            ->setHelp('This command allows you to create a new check file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the check')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the check'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Check Generator');

        $checkName = $input->getArgument('name');
        $createDir = $input->getOption('dir');

        try {
            $this->createCheckFile($checkName, $createDir, $io);
            $io->success("Check '{$checkName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createCheckFile(string $checkName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Checks" : '');

        $checkTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use ModulesPress\\Foundation\\Guard\\Contracts\\CanActivate;
use ModulesPress\\Core\\ExecutionContext\\ExecutionContext;

class {$checkName} implements CanActivate
{
    public function canActivate(ExecutionContext \$executionContext): bool
    {
        return true;
    }
}
PHP;

        if ($createDir) {
            $checkDir = "Checks";
            if (!is_dir($checkDir)) {
                mkdir($checkDir, 0777, true);
            }
            $checkFile = $checkDir . "/{$checkName}.php";
        } else {
            $checkFile = "{$checkName}.php";
        }

        if (file_exists($checkFile)) {
            throw new \Exception("Check '{$checkName}' already exists!");
        }

        file_put_contents($checkFile, $checkTemplate);
        $io->note("Created check in namespace: " . $completeNamespace);
    }
}
