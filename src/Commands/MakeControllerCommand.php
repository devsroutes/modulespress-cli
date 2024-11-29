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

class MakeControllerCommand extends Command
{
    protected static $defaultName = 'make:controller';
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
            ->setDescription('Creates a new controller file.')
            ->setHelp('This command allows you to create a new controller file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the controller'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Controller Generator');

        $controllerName = $input->getArgument('name');
        $createDir = $input->getOption('dir');

        try {
            $this->createControllerFile($controllerName, $createDir, $io);
            $io->success("Controller '{$controllerName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createControllerFile(string $controllerName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Controllers" : '');

        // Remove "Controller" suffix if present for the route name
        $routeName = str_replace('Controller', '', $controllerName);
        $routeName = strtolower($routeName);

        $controllerTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use Symfony\\Component\\Validator\\Constraints;
use ModulesPress\\Foundation\\Http\\Attributes\\{RestController, Get, Post, Put, Delete, Param, Body};
use ModulesPress\\Foundation\\DI\\Attributes\\Injectable;

#[Injectable]
#[RestController("/{$routeName}")]
class {$controllerName}
{
    public function __construct() {}

    #[Get(':id')]
    public function get(
        #[Param(key: "id", rules: [new Constraints\\Length(min: 1)])] string \$id
    ) {
        return [
            "id" => \$id,
            "message" => "Retrieved successfully",
        ];
    }

    #[Post]
    public function create(
        #[Body] array \$body
    ) {
        return [
            "body" => \$body,
            "message" => "Created successfully",
        ];
    }

    #[Put(':id')]
    public function update(
        #[Param(key: "id", rules: [new Constraints\\Length(min: 1)])] string \$id,
        #[Body] array \$body
    ) {
        return [
            "id" => \$id,
            "body" => \$body,
            "message" => "Updated successfully",
        ];
    }

    #[Delete(':id')]
    public function delete(
        #[Param(key: "id", rules: [new Constraints\\Length(min: 1)])] string \$id
    ) {
        return [
            "id" => \$id,
            "message" => "Deleted successfully",
        ];
    }
}
PHP;

        if ($createDir) {
            $controllerDir = "Controllers";
            if (!is_dir($controllerDir)) {
                mkdir($controllerDir, 0777, true);
            }
            $controllerFile = $controllerDir . "/{$controllerName}.php";
        } else {
            $controllerFile = "{$controllerName}.php";
        }

        if (file_exists($controllerFile)) {
            throw new \Exception("Controller '{$controllerName}' already exists!");
        }

        file_put_contents($controllerFile, $controllerTemplate);
        $io->note("Created controller in namespace: " . $completeNamespace);
    }
}
