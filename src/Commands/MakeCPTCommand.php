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

class MakeCPTCommand extends Command
{
    protected static $defaultName = 'make:cpt';
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
            ->setDescription('Creates a new Custom Post Type entity file.')
            ->setHelp('This command allows you to create a new CPT entity file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the CPT entity')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the entity'
            )
            ->addOption(
                'singular',
                's',
                InputOption::VALUE_REQUIRED,
                'The singular name for the CPT'
            )
            ->addOption(
                'plural',
                'p',
                InputOption::VALUE_REQUIRED,
                'The plural name for the CPT'
            )
            ->addOption(
                'post-type',
                't',
                InputOption::VALUE_REQUIRED,
                'The post type name (slug) for the CPT'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress CPT Entity Generator');

        $entityName = $input->getArgument('name');
        $createDir = $input->getOption('dir');
        
        // Get or generate names
        $singular = $input->getOption('singular') ?? str_replace('Entity', '', $entityName);
        $plural = $input->getOption('plural') ?? $singular . 's';
        $postType = $input->getOption('post-type') ?? 'cpt_' . strtolower($singular);

        try {
            $this->createCPTFile($entityName, $createDir, $singular, $plural, $postType, $io);
            $io->success("CPT Entity '{$entityName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createCPTFile(string $entityName, bool $createDir, string $singular, string $plural, string $postType, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Entities" : '');

        $cptTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use Symfony\\Component\\Validator\\Constraints;
use ModulesPress\\Foundation\\Entity\\CPT\\Attributes\\{CustomPostType, MetaField};
use ModulesPress\\Foundation\\Entity\\CPT\\CPTEntity;

#[CustomPostType(
    name: '{$postType}',
    singular: '{$singular}',
    plural: '{$plural}',
    args: [
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-admin-post'
    ]
)]
class {$entityName} extends CPTEntity
{
    #[Constraints\\Length(min: 5, max: 255)]
    #[Constraints\\NotNull]
    #[MetaField]
    public string \$title;

    #[Constraints\\Length(min: 10)]
    #[Constraints\\NotNull]
    #[MetaField]
    public string \$description;
}
PHP;

        if ($createDir) {
            $entityDir = "Entities";
            if (!is_dir($entityDir)) {
                mkdir($entityDir, 0777, true);
            }
            $entityFile = $entityDir . "/{$entityName}.php";
        } else {
            $entityFile = "{$entityName}.php";
        }

        if (file_exists($entityFile)) {
            throw new \Exception("CPT Entity '{$entityName}' already exists!");
        }

        file_put_contents($entityFile, $cptTemplate);
        $io->note("Created CPT entity in namespace: " . $completeNamespace);
    }
}
