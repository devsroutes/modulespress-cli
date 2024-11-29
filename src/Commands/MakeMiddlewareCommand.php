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

class MakeMiddlewareCommand extends Command
{
    protected static $defaultName = 'make:middleware';
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
            ->setDescription('Creates a new middleware file.')
            ->setHelp('This command allows you to create a new middleware file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the middleware')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the middleware'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Middleware Generator');

        $middlewareName = $input->getArgument('name');
        $createDir = $input->getOption('dir');

        try {
            $this->createMiddlewareFile($middlewareName, $createDir, $io);
            $io->success("Middleware '{$middlewareName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createMiddlewareFile(string $middlewareName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Middlewares" : '');

        $middlewareTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use WP_REST_Request;
use WP_REST_Response;
use ModulesPress\\Foundation\\Http\\Contracts\\Middleware;

class {$middlewareName} implements Middleware
{
    public function use(
        WP_REST_Request \$req,
        WP_REST_Response \$res
    ): WP_REST_Request|WP_REST_Response {
        return \$req;
    }
}
PHP;

        if ($createDir) {
            $middlewareDir = "Middlewares";
            if (!is_dir($middlewareDir)) {
                mkdir($middlewareDir, 0777, true);
            }
            $middlewareFile = $middlewareDir . "/{$middlewareName}.php";
        } else {
            $middlewareFile = "{$middlewareName}.php";
        }

        if (file_exists($middlewareFile)) {
            throw new \Exception("Middleware '{$middlewareName}' already exists!");
        }

        file_put_contents($middlewareFile, $middlewareTemplate);
        $io->note("Created middleware in namespace: " . $completeNamespace);
    }
}
