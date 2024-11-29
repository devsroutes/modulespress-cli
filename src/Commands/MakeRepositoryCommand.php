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

class MakeRepositoryCommand extends Command
{
    protected static $defaultName = 'make:repository';
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
            ->setDescription('Creates a new repository file.')
            ->setHelp('This command allows you to create a new repository file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the repository')
            ->addArgument('entity', InputArgument::REQUIRED, 'The name of the entity class')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the repository'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Repository Generator');

        $repositoryName = $input->getArgument('name');
        $entityName = $input->getArgument('entity');
        $createDir = $input->getOption('dir');

        try {
            $this->createRepositoryFile($repositoryName, $entityName, $createDir, $io);
            $io->success("Repository '{$repositoryName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createRepositoryFile(string $repositoryName, string $entityName, bool $createDir, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Repositories" : '');

        // Determine entity namespace
        $entityNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, "Entities");

        $repositoryTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use ModulesPress\\Foundation\\Entity\\CPT\\CPTEntity;
use ModulesPress\\Foundation\\Entity\\CPT\\Repositories\\CPTRepository;
use ModulesPress\\Foundation\\DI\\Attributes\\Injectable;
use {$entityNamespace}\\{$entityName};

#[Injectable]
class {$repositoryName} extends CPTRepository
{
    public function __construct()
    {
        parent::__construct({$entityName}::class);
    }

    /**
     * @return {$entityName}
     */
    public function find(int \$id): ?CPTEntity
    {
        return parent::find(\$id);
    }

    /**
     * @return {$entityName}[]
     */
    public function findBy(array \$args): array
    {
        return parent::findBy(\$args);
    }

    /**
     * @return {$entityName}[]
     */
    public function findAll(string \$order = 'ASC', string \$orderBy = 'ID'): array
    {
        return parent::findAll(\$order, \$orderBy);
    }
}
PHP;

        if ($createDir) {
            $repositoryDir = "Repositories";
            if (!is_dir($repositoryDir)) {
                mkdir($repositoryDir, 0777, true);
            }
            $repositoryFile = $repositoryDir . "/{$repositoryName}.php";
        } else {
            $repositoryFile = "{$repositoryName}.php";
        }

        if (file_exists($repositoryFile)) {
            throw new \Exception("Repository '{$repositoryName}' already exists!");
        }

        file_put_contents($repositoryFile, $repositoryTemplate);
        $io->note("Created repository in namespace: " . $completeNamespace);
    }
}
