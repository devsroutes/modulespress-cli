<?php

namespace ModulesPressCLI\Services;

class TextReplacerService
{
    const STARTER_PLUGIN_NAME = "Skeleton Plugin";
    const STARTER_PLUGIN_NAMESPACE = "Skeleton";
    const STARTER_PLUGIN_CLASS_NAME = "SkeletonPlugin";
    const STARTER_PLUGIN_SLUG = "skeleton-plugin";
    const STARTER_PLUGIN_PREFIX = "sp_";
    const STARTER_PLUGIN_DESCRIPTION = "A starter plugin based on ModulesPress foundation.";
    const STARTER_PLUGIN_AUTHOR_NAME = "Devs Routes Co";

    public function __construct(
        private readonly string $newPluginName,
        private readonly string $newPluginClassName,
        private readonly string $newPluginSlug,
        private readonly string $newPluginPrefix,
        private readonly string $newPluginNamespace,
        private readonly string $newPluginDescription,
        private readonly string $newPluginAuthorName,
    ) {}

    public function replacePluginName($content)
    {
        $content = str_replace(self::STARTER_PLUGIN_NAME, $this->newPluginName, $content);
        return $content;
    }

    public function replacePluginDescription($content)
    {
        $content = str_replace(self::STARTER_PLUGIN_DESCRIPTION, $this->newPluginDescription, $content);
        return $content;
    }

    public function replacePluginAuthorName($content)
    {
        $content = str_replace(self::STARTER_PLUGIN_AUTHOR_NAME, $this->newPluginAuthorName, $content);
        return $content;
    }

    public function replacePluginClassName($content)
    {
        $content = str_replace(self::STARTER_PLUGIN_CLASS_NAME, $this->newPluginClassName, $content);
        return $content;
    }

    public function replacePluginSlug($content)
    {
        $content = str_replace(self::STARTER_PLUGIN_SLUG, $this->newPluginSlug, $content);
        return $content;
    }

    public function replacePluginPrefix($content)
    {
        $content = str_replace(self::STARTER_PLUGIN_PREFIX, $this->newPluginPrefix, $content);
        return $content;
    }

    public function replaceNamespaceDeclaration($content)
    {
        $newNamespace = $this->newPluginNamespace;
        // Replace the primary namespace declaration (handles nested namespaces too)
        $content = preg_replace(
            "/\bnamespace\s+" . preg_quote(self::STARTER_PLUGIN_NAMESPACE, '/') . "(\\\\[a-zA-Z0-9_\\\\]*)?\s*;/",
            "namespace $newNamespace$1;",
            $content
        );
        return $content;
    }

    public function replaceUsedNamespaces($content)
    {
        $newNamespace = $this->newPluginNamespace;
        // Replace the primary namespace declaration (handles nested namespaces too)
        $content = preg_replace(
            "/\buse\s+" . preg_quote(self::STARTER_PLUGIN_NAMESPACE, '/') . "(\\\\[a-zA-Z0-9_\\\\]*)?;/",
            "use $newNamespace$1;",
            $content
        );
        return $content;
    }

    public function replaceUsedModulesPressNamespace($content)
    {
        $newNamespace = $this->newPluginNamespace;
        // Replace the primary namespace declaration (handles nested namespaces too)
        $content = preg_replace(
            "/\buse\s+ModulesPress\\\\(\\\\[a-zA-Z0-9_\\\\]*)?;/",
          'use ' . $newNamespace . '\\Vendor\\ModulesPress$1;',
            $content
        );
        return $content;
    }

}
