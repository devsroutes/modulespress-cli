<?php

namespace ModulesPressCLI\Services;

use Exception;
use ModulesPressCLI\Common\Plugin;

class PluginExtractionService
{
    private readonly CommonService $commonService;

    public function __construct()
    {
        $this->commonService = new CommonService();
    }

    public function getProjectRootDir($maxDepth = 10)
    {
        $currentDir = getcwd();
        $depth = 0;

        while (!file_exists($currentDir . '/composer.json') && $currentDir !== '/' && $depth < $maxDepth) {
            $currentDir = dirname($currentDir);
            $depth++;
        }

        if (!file_exists($currentDir . '/composer.json') || $depth >= $maxDepth) {
            throw new Exception('Could not find project root directory (composer.json not found within depth limit)');
        }

        return $currentDir;
    }

    public function findMainPluginFile($pluginDir = null)
    {
        if ($pluginDir === null) {
            $pluginDir = $this->getProjectRootDir();
        }

        $phpFiles = glob($pluginDir . '/*.php');

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'Plugin Name:') !== false) {
                return $file;
            }
        }

        return null;
    }

    public function extractPluginHeaders($content)
    {
        $headers = [
            'Plugin Name' => '',
            'Version' => '',
            'Description' => '',
            'Author' => '',
            'Text Domain' => ''
        ];

        foreach ($headers as $header => &$value) {
            if (preg_match('/' . preg_quote($header, '/') . ':\s*(.+?)$/m', $content, $matches)) {
                $value = trim($matches[1]);
            }
        }

        return $headers;
    }

    public function parsePluginHeader(): Plugin
    {
        $mainFile = $this->findMainPluginFile();
        if (!$mainFile) {
            throw new Exception("Could not find main plugin file");
        }

        $content = file_get_contents($mainFile);
        if ($content === false) {
            throw new Exception("Could not read plugin file: $mainFile");
        }

        $headers = $this->extractPluginHeaders($content);

        if (empty($headers['Plugin Name']) || empty($headers['Version'])) {
            throw new Exception("Required plugin headers not found");
        }

        return new Plugin(
            $headers['Plugin Name'],
            $headers['Version'],
            $this->commonService->generateSlug($headers['Plugin Name']),
            $headers
        );
    }

    public function getPluginBaseNamespace($pluginDir = null): string
    {
        if (!$pluginDir)
            $pluginDir = $this->getProjectRootDir();
        $composerJson = json_decode(file_get_contents($pluginDir . '/composer.json'), true);
        $autoloadDefinition = $composerJson['autoload']['psr-4'];
        $baseNamespace = array_keys($autoloadDefinition)[0];
        return $baseNamespace;
    }

    public function createPathNamespaceFromActiveDirectory()
    {
        $currentDir = getcwd();
        $namespace = '';
        while (basename($currentDir) !== 'src') {
            $namespace = basename($currentDir) . '\\' . $namespace;
            $currentDir = dirname($currentDir);
        }
        return $namespace;
    }

    public function findModuleInDirectory($currentDir = null, $maxDepth = 10)
    {
        if ($currentDir === null) {
            $currentDir = getcwd();
        }

        $depth = 0;

        while ($depth < $maxDepth && $currentDir !== '/') {
            $phpFiles = glob($currentDir . '/*.php');

            foreach ($phpFiles as $file) {
                $content = file_get_contents($file);
                if (strpos($content, '#[Module') !== false) {
                    return $file;
                }
            }

            $currentDir = dirname($currentDir);
            $depth++;
        }

        throw new Exception("Could not find a module within the depth limit of $maxDepth.");
    }
}
