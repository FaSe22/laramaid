<?php

namespace Fase22\Laramaid\Mermaid;

class MermaidDiagramBuilder
{
    public function build(array $namespaces): string
    {
        $diagram = ["classDiagram\n"];

        foreach ($namespaces as $namespaceName => $classes) {
            $diagram[] = "namespace $namespaceName {";

            foreach ($classes as $class) {
                $diagram[] = $this->formatClass($class);
            }

            $diagram[] = "}\n";
        }

        return implode("\n", $diagram);
    }

    private function formatClass(MermaidClass $class): string
    {
        $lines = ["    class {$class->name} {"];

        foreach ($class->properties as $prop) {
            $lines[] = $this->formatProperty($prop);
        }

        foreach ($class->methods as $method) {
            $lines[] = $this->formatMethod($method);
        }

        $lines[] = '    }';

        return implode("\n", $lines);
    }

    private function formatProperty(MermaidProperty $property): string
    {
        return sprintf(
            '        %s%s: %s',
            $this->getVisibilitySymbol($property->visibility),
            $property->name,
            $property->type
        );
    }

    private function formatMethod(MermaidMethod $method): string
    {
        $params = array_map(
            fn (MermaidParameter $p) => "{$p->name}: {$p->type}",
            $method->parameters
        );

        return sprintf(
            '        %s%s(%s): %s',
            $this->getVisibilitySymbol($method->visibility),
            $method->name,
            implode(', ', $params),
            $method->returnType
        );
    }

    private function getVisibilitySymbol(string $visibility): string
    {
        return match ($visibility) {
            'public' => '+',
            'private' => '-',
            'protected' => '#',
            default => '+'
        };
    }
}
