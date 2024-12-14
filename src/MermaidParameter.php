<?php

namespace Fase22\Laramaid;

class MermaidParameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $type
    ) {
    }
}
