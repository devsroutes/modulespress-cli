<?php

namespace ModulesPressCLI\Services;

class CommonService
{
    public function generateSlug($name)
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    public function toPascalCase(string $text): string
    {
        // Remove any non-alphanumeric characters and convert to PascalCase
        $text = preg_replace('/[^a-zA-Z0-9]+/', ' ', $text);
        return str_replace(' ', '', ucwords($text));
    }

    public function generatePrefix(string $name): string
    {
        // Convert the name to lowercase and trim whitespace
        $name = strtolower(trim($name));
        
        // Split the name into words based on spaces or uppercase letters
        $words = preg_split('/(?=[A-Z])|[\s_]+/', $name);
    
        // Get the first letter of each word
        $prefix = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $prefix .= $word[0];
            }
        }
    
        // Return the prefix with an underscore at the end
        return $prefix . '_';
    }

    public function generateNamespace(
        string $baseNamespace,
        string $pathNamespace,
        string $classNamespace
    ): string
    {
        $namespace = rtrim($baseNamespace, '\\') . '\\' . $pathNamespace . ltrim($classNamespace . '\\', '\\');
        $generatedNamespace = substr($namespace, 0, -1);
        return $generatedNamespace;
    }
    

}
