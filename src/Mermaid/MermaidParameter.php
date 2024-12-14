<?php

namespace Fase22\Laramaid\Mermaid;

class MermaidParameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $type
    ) {
    }
}
