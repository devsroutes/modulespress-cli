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

class MakeTaxonomyCommand extends Command
{
    protected static $defaultName = 'make:taxonomy';
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
            ->setDescription('Creates a new taxonomy file.')
            ->setHelp('This command allows you to create a new taxonomy file with the basic structure.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the taxonomy')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_NONE,
                'Create a directory for the taxonomy'
            )
            ->addOption(
                'singular',
                's',
                InputOption::VALUE_REQUIRED,
                'The singular name for the taxonomy'
            )
            ->addOption(
                'plural',
                'p',
                InputOption::VALUE_REQUIRED,
                'The plural name for the taxonomy'
            )
            ->addOption(
                'slug',
                't',
                InputOption::VALUE_REQUIRED,
                'The taxonomy slug'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📦 ModulesPress Taxonomy Generator');

        $taxonomyName = $input->getArgument('name');
        $createDir = $input->getOption('dir');
        
        // Get or generate names
        $baseName = str_replace('Taxonomy', '', $taxonomyName);
        $singular = $input->getOption('singular') ?? $baseName;
        $plural = $input->getOption('plural') ?? $singular . 's';
        $slug = $input->getOption('slug') ?? strtolower($baseName);

        try {
            $this->createTaxonomyFile($taxonomyName, $createDir, $singular, $plural, $slug, $io);
            $io->success("Taxonomy '{$taxonomyName}' created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createTaxonomyFile(string $taxonomyName, bool $createDir, string $singular, string $plural, string $slug, SymfonyStyle $io)
    {
        $baseNamespace = $this->pluginExtractionService->getPluginBaseNamespace();
        $pathNamespace = $this->pluginExtractionService->createPathNamespaceFromActiveDirectory();
        $completeNamespace = $this->commonService->generateNamespace($baseNamespace, $pathNamespace, $createDir ? "Taxonomies" : '');

        $taxonomyTemplate = <<<PHP
<?php

namespace {$completeNamespace};

use ModulesPress\\Foundation\\Entity\\CPT\\Attributes\\Taxonomy;

#[Taxonomy(
    slug: '{$slug}',
    singular: "{$singular}",
    plural: "{$plural}",
    args: [
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'show_admin_column' => true
    ]
)]
class {$taxonomyName} {}
PHP;

        if ($createDir) {
            $taxonomyDir = "Taxonomies";
            if (!is_dir($taxonomyDir)) {
                mkdir($taxonomyDir, 0777, true);
            }
            $taxonomyFile = $taxonomyDir . "/{$taxonomyName}.php";
        } else {
            $taxonomyFile = "{$taxonomyName}.php";
        }

        if (file_exists($taxonomyFile)) {
            throw new \Exception("Taxonomy '{$taxonomyName}' already exists!");
        }

        file_put_contents($taxonomyFile, $taxonomyTemplate);
        $io->note("Created taxonomy in namespace: " . $completeNamespace);
    }
}
