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

class MakeFilterCommand extends Command
{
    protected static $defaultName = 'make:filter';
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
            ->setDescription('Creates a new exception filter file.')
            ->setHelp('This command allows you to create a new exception filter file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the filter')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the filter'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Filter Generator');

        $filterName = $input->getArgument('name');
        $createDir = $input->getOption('dir');

        try {
            $this->createFilterFile($filterName, $createDir, $io);
            $io->success("Filter '{$filterName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createFilterFile(string $filterName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Filters" : '');

        $filterTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use WP_REST_Response;
use ModulesPress\\Foundation\\Exception\\BaseException;
use ModulesPress\\Foundation\\Exception\\Attributes\\CatchException;
use ModulesPress\\Foundation\\Exception\\Contracts\\ExceptionFilter;
use ModulesPress\\Foundation\\Http\\Responses\\{JsonResponse, HtmlResponse};
use ModulesPress\\Core\\ExecutionContext\\ExecutionContext;

#[CatchException]
class {$filterName} implements ExceptionFilter
{
    public function catchException(
        BaseException \$e,
        ExecutionContext \$ctx
    ): WP_REST_Response | HtmlResponse | JsonResponse {
        throw \$e;
    }
}
PHP;

        if ($createDir) {
            $filterDir = "Filters";
            if (!is_dir($filterDir)) {
                mkdir($filterDir, 0777, true);
            }
            $filterFile = $filterDir . "/{$filterName}.php";
        } else {
            $filterFile = "{$filterName}.php";
        }

        if (file_exists($filterFile)) {
            throw new \Exception("Filter '{$filterName}' already exists!");
        }

        file_put_contents($filterFile, $filterTemplate);
        $io->note("Created filter in namespace: " . $completeNamespace);
    }
}
