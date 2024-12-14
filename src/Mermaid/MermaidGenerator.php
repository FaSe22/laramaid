<?php

namespace Fase22\Laramaid\Mermaid;

class MermaidGenerator
{
    public function __construct(
        private readonly MermaidDiagramBuilder $builder
    ) {}

    public function generate(array $namespaces): string
    {
        return $this->builder->build($namespaces);
    }
}
