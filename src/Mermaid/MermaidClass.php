<?php

namespace Fase22\Laramaid\Mermaid;

class MermaidClass
{
    public function __construct(
        public readonly string $name,
        public readonly array $methods,
        public readonly array $properties
    ) {}
}
