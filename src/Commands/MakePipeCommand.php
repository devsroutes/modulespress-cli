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

class MakePipeCommand extends Command
{
    protected static $defaultName = 'make:pipe';
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
            ->setDescription('Creates a new pipe transformation file.')
            ->setHelp('This command allows you to create a new pipe transformation file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the pipe')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the pipe'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Pipe Generator');

        $pipeName = $input->getArgument('name');
        $createDir = $input->getOption('dir');

        try {
            $this->createPipeFile($pipeName, $createDir, $io);
            $io->success("Pipe '{$pipeName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createPipeFile(string $pipeName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Pipes" : '');

        $pipeTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use ModulesPress\\Foundation\\Http\\Contracts\\PipeTransform;

class {$pipeName} implements PipeTransform
{
    public function transform(mixed \$value): mixed
    {
        return \$value;
    }
}
PHP;

        if ($createDir) {
            $pipeDir = "Pipes";
            if (!is_dir($pipeDir)) {
                mkdir($pipeDir, 0777, true);
            }
            $pipeFile = $pipeDir . "/{$pipeName}.php";
        } else {
            $pipeFile = "{$pipeName}.php";
        }

        if (file_exists($pipeFile)) {
            throw new \Exception("Pipe '{$pipeName}' already exists!");
        }

        file_put_contents($pipeFile, $pipeTemplate);
        $io->note("Created pipe in namespace: " . $completeNamespace);
    }
}
