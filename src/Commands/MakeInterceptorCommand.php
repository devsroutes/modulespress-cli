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

class MakeInterceptorCommand extends Command
{
    protected static $defaultName = 'make:interceptor';
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
            ->setDescription('Creates a new interceptor file.')
            ->setHelp('This command allows you to create a new interceptor file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the interceptor')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the interceptor'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Interceptor Generator');

        $interceptorName = $input->getArgument('name');
        $createDir = $input->getOption('dir');

        try {
            $this->createInterceptorFile($interceptorName, $createDir, $io);
            $io->success("Interceptor '{$interceptorName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createInterceptorFile(string $interceptorName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Interceptors" : '');

        $interceptorTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use ModulesPress\\Foundation\\Http\\Contracts\\Interceptor;
use ModulesPress\\Core\\ExecutionContext\\ExecutionContext;
use ModulesPress\\Core\\Http\\CallHandler;

class {$interceptorName} implements Interceptor
{
    public function intercept(ExecutionContext \$executionContext, CallHandler \$next): mixed
    {
        \$result = \$next->handle();
        return \$result;
    }
}
PHP;

        if ($createDir) {
            $interceptorDir = "Interceptors";
            if (!is_dir($interceptorDir)) {
                mkdir($interceptorDir, 0777, true);
            }
            $interceptorFile = $interceptorDir . "/{$interceptorName}.php";
        } else {
            $interceptorFile = "{$interceptorName}.php";
        }

        if (file_exists($interceptorFile)) {
            throw new \Exception("Interceptor '{$interceptorName}' already exists!");
        }

        file_put_contents($interceptorFile, $interceptorTemplate);
        $io->note("Created interceptor in namespace: " . $completeNamespace);
    }
}
