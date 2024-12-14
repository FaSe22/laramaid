<?php

namespace Fase22\Laramaid\Laravel;

interface ClassGeneratorInterface
{
    public function generate(string $targetDirectory, array $namespaceData): void;
}
