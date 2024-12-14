<?php

namespace Fase22\Laramaid\Mermaid;

class MermaidMethod
{
    public function __construct(
        public readonly string $name,
        public readonly string $visibility,
        public readonly array $parameters,
        public readonly string $returnType
    ) {
    }

    public function toPhp(): string
    {
        $params = array_map(fn ($param) => "{$param->type} \${$param->name}", $this->parameters);
        return sprintf(
            "    /**\n     * %s\n     * @param %s\n     * @return %s\n     */\n    %s function %s(%s): %s\n    {\n        //TODO: Implement %s\n    }\n",
            ucfirst($this->name),
            implode(', ', $params),
            $this->returnType,
            $this->visibility,
            $this->name,
            implode(', ', $params),
            $this->returnType,
            $this->name
        );
    }
}
