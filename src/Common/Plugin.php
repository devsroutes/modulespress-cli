<?php

namespace ModulesPressCLI\Common;

class Plugin
{
    public function __construct(
        private readonly string $name,
        private readonly string $version,
        private readonly string $slug
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
