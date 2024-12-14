<?php

namespace Fase22\Laramaid;

class MermaidProperty
{
    public function __construct(
        public readonly string $name,
        public readonly string $visibility,
        public readonly string $type
    ) {
    }

    public function toPhp(): string
    {
        return sprintf(
            "    /**\n     * @var %s\n     */\n    %s %s $%s;\n",
            $this->type,
            $this->visibility,
            $this->type,
            $this->name
        );
    }
}
