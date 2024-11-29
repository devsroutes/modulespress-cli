<?php

namespace ModulesPressCLI\Commands;

use ModulesPressCLI\Services\PluginExtractionService;
use ModulesPressCLI\Services\CommonService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeModuleCommand extends Command
{
    protected static $defaultName = 'make:module';
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
            ->setDescription('Creates a new module file.')
            ->setHelp('This command allows you to create a new module file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the module');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Module Generator');

        $moduleName = $input->getArgument('name');
        
        try {
    
            $this->createModuleFile($moduleName, $io);
            $io->success("Module '{$moduleName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createModuleFile(string $moduleName, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $moduleName);

        $moduleTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use ModulesPress\\Foundation\\Module\\Attributes\\Module;
use ModulesPress\\Foundation\\Module\\ModulesPressModule;

#[Module(
    imports: [],
    providers: [],
    controllers: [],
    entities: [],
    exports: []
)]
class {$moduleName} extends ModulesPressModule {}
PHP;

        // Create module in the same directory as the main plugin file
        $modulesDir = $moduleName;
        if (!is_dir($modulesDir)) {
            mkdir($modulesDir, 0777, true);
        }

        $moduleFile = $modulesDir . "/{$moduleName}.php";
        if (file_exists($moduleFile)) {
            throw new \Exception("Module '{$moduleName}' already exists!");
        }

        file_put_contents($moduleFile, $moduleTemplate);
        $io->note("Created module in namespace: " . $completeNamespace);
    }
}
