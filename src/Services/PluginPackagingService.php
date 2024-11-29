<?php

namespace ModulesPressCLI\Services;

use Symfony\Component\Console\Style\SymfonyStyle;

use DirectoryIterator;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class PluginPackagingService
{
    private $pluginName;
    private $pluginVersion;
    private $pluginSlug;
    private $zip;
    private $rootDir;
    private $tempDir;
    private $io;

    private PluginExtractionService $pluginExtractionService;

    // Define the exclusion patterns
    private $excludePatterns = [
        ".cache",         // Cache files
        "composer.*",      // Composer files
        "package.*",       // Package files
        "package-lock.*",  // Package lock files
        "vite.config.*",   // Vite config files
        "vite-env.*",      // Vite environment files
        "tsconfig.*",      // TypeScript config files
        "node_modules",    // Node modules folder
        "resources",       // Resources folder
        "static",         // Static folder
        "bin",            // Bin folder   
        "artifacts",      // Artifacts folder
        ".git",           // Git directory
        ".github",        // GitHub directory
        "tests",          // Test directory
        ".gitignore",     // Git ignore file
        "README.md"       // README file
    ];

    // Files/directories to copy even if they match exclude patterns
    private $forceInclude = [
        "vendor",         // Include vendor after composer install
        "ModulesPress"    // Include your framework directory
    ];

    public function __construct(InputInterface $input, ConsoleOutput $output,  $verbose = false, SymfonyStyle $io = null)
    {
        $this->pluginExtractionService = new PluginExtractionService();
        $this->io = $io ?: new SymfonyStyle($input, $output);
        $this->rootDir =  $this->pluginExtractionService->getProjectRootDir();
        $this->zip = new ZipArchive();
        $this->tempDir = sys_get_temp_dir() . '/plugin-build-' . uniqid();
        $this->io->section("Parsing plugin header");
        $plugin = $this->pluginExtractionService->parsePluginHeader();
        $this->pluginName = $plugin->getName();
        $this->pluginVersion = $plugin->getVersion();
        $this->pluginSlug = $plugin->getSlug();
        $this->io->writeln("Name: {$this->pluginName}");
        $this->io->writeln("Version: {$this->pluginVersion}");
        $this->io->writeln("Slug: {$this->pluginSlug}");
    }

    private function rrmdir($dir)
    {
        if (!is_dir($dir)) return;

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    private function cleanup()
    {
        $this->rrmdir($this->tempDir);
    }

    public function package()
    {
        try {
            $this->prepareTempDirectory();
            $this->copyFiles();
            $this->installDependencies();
            $this->createZip();
            $this->cleanup();
        } catch (Exception $e) {
            $this->io->error("Error: " . $e->getMessage());
            $this->cleanup();
            exit(1);
        }
    }

    private function prepareTempDirectory()
    {
        $this->io->section("Preparing build environment");

        if (file_exists($this->tempDir)) {
            $this->rrmdir($this->tempDir);
        }
        mkdir($this->tempDir, 0777, true);
        $this->io->writeln("Created temp directory: " . basename($this->tempDir));

        if (!file_exists($this->rootDir . '/artifacts')) {
            mkdir($this->rootDir . '/artifacts', 0777, true);
            $this->io->writeln("Created artifacts directory");
        }
    }

    private function shouldInclude($path)
    {
        foreach ($this->forceInclude as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        foreach ($this->excludePatterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return false;
            }
        }

        return true;
    }

    private function copyDirectory($source, $dest)
    {
        $dir = opendir($source);
        @mkdir($dest);

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;

            $sourcePath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;
            $relPath = substr($sourcePath, strlen($this->rootDir) + 1);

            if (!$this->shouldInclude($relPath)) {
                $this->io->text("Skipping: $relPath");
                continue;
            }

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }

        closedir($dir);
    }

    private function copyFiles()
    {
        $this->io->section("Copying plugin files...");
        $this->copyDirectory($this->rootDir, $this->tempDir);
    }

    private function installDependencies()
    {
        $this->io->section("Installing production dependencies...");

        copy($this->rootDir . '/composer.json', $this->tempDir . '/composer.json');
        $this->io->text("Copied composer.json");

        $command = sprintf(
            'cd %s && composer install --no-dev --optimize-autoloader 2>&1',
            escapeshellarg($this->tempDir)
        );

        $this->io->text("Running composer install...");
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("Composer install failed:\n" . implode("\n", $output));
        }

        unlink($this->tempDir . '/composer.json');
        if (file_exists($this->tempDir . '/composer.lock')) {
            unlink($this->tempDir . '/composer.lock');
        }
        $this->io->text("Cleaned up composer files");
    }

    private function addDirToZip($dir, $zipPath)
    {
        $files = new DirectoryIterator($dir);

        foreach ($files as $file) {
            if ($file->isDot()) continue;

            $filePath = $file->getRealPath();
            $relativePath = $zipPath . '/' . $file->getFilename();

            if ($file->isDir()) {
                $this->zip->addEmptyDir($relativePath);
                $this->addDirToZip($filePath, $relativePath);
            } else {
                $this->zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function createZip()
    {
        $zipName = sprintf('%s-v%s.zip', $this->pluginSlug, $this->pluginVersion);
        $zipPath = $this->rootDir . '/artifacts/' . $zipName;

        $this->io->section("Creating a zip file");
        $this->io->writeln("Package name: $zipName");

        if (file_exists($zipPath)) {
            unlink($zipPath);
            $this->io->writeln("Removed existing package");
        }

        if (!$this->zip->open($zipPath, ZipArchive::CREATE)) {
            throw new Exception('Failed to create zip file');
        }

        $baseDir = $this->pluginSlug;
        $this->zip->addEmptyDir($baseDir);
        $this->addDirToZip($this->tempDir, $baseDir);
        $this->zip->close();

        $this->io->section("Build Details");
        $this->io->writeln("Location: artifacts/$zipName");
        $this->io->writeln("Size: " . $this->formatBytes(filesize($zipPath)));

        $this->io->success("Artifact generated.");
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
