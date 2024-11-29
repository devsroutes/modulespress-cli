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

class MakeGuardCommand extends Command
{
    protected static $defaultName = 'make:guard';
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
            ->setDescription('Creates a new guard file.')
            ->setHelp('This command allows you to create a new guard file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the guard')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the guard'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Guard Generator');

        $guardName = $input->getArgument('name');
        $createDir = $input->getOption('dir');

        try {
            $this->createGuardFile($guardName, $createDir, $io);
            $io->success("Guard '{$guardName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createGuardFile(string $guardName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Guards" : '');

        $guardTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use ModulesPress\\Foundation\\Guard\\Contracts\\CanActivate;
use ModulesPress\\Core\\ExecutionContext\\ExecutionContext;

class {$guardName} implements CanActivate
{
    public function canActivate(ExecutionContext \$ctx): bool
    {
        return true;
    }
}
PHP;

        if ($createDir) {
            $guardDir = "Guards";
            if (!is_dir($guardDir)) {
                mkdir($guardDir, 0777, true);
            }
            $guardFile = $guardDir . "/{$guardName}.php";
        } else {
            $guardFile = "{$guardName}.php";
        }

        if (file_exists($guardFile)) {
            throw new \Exception("Guard '{$guardName}' already exists!");
        }

        file_put_contents($guardFile, $guardTemplate);
        $io->note("Created guard in namespace: " . $completeNamespace);
    }
}
