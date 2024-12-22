<?php

namespace Fase22\Laramaid\Laravel;

use Fase22\Laramaid\Mermaid\MermaidClass;
use Fase22\Laramaid\Mermaid\MermaidMethod;
use Fase22\Laramaid\Mermaid\MermaidParameter;
use Fase22\Laramaid\Mermaid\MermaidProperty;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

class LaravelClassExtractor
{
    private $parser;

    private $nodeFinder;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForVersion(PhpVersion::getHostVersion());
        $this->nodeFinder = new NodeFinder;
    }

    public function extractFromDirectory(string $directory): array
    {
        $files = $this->findPhpFiles($directory);

        return $this->analyzeFiles($files);
    }

    private function findPhpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function analyzeFiles(array $files): array
    {
        $namespaces = [];

        foreach ($files as $file) {
            $code = file_get_contents($file);
            $ast = $this->parser->parse($code);

            $namespace = $this->nodeFinder->findFirst($ast, fn (Node $node) => $node instanceof Namespace_);
            if (! $namespace) {
                continue;
            }

            $namespaceName = $this->normalizeNamespace($namespace->name->toString());
            if (! isset($namespaces[$namespaceName])) {
                $namespaces[$namespaceName] = [];
            }

            $classes = $this->nodeFinder->findInstanceOf($namespace, Class_::class);
            foreach ($classes as $class) {
                $namespaces[$namespaceName][] = $this->createMermaidClass($class);
            }
        }

        return array_filter($namespaces, fn ($classes) => ! empty($classes));
    }

    private function normalizeNamespace(string $namespace): string
    {
        $namespace = str_replace('\\', '.', $namespace);

        $mappings = [
            'Http.Controllers' => 'Controllers',
            'Models' => 'Models',
            'Events' => 'Events',
            'Listeners' => 'Listeners',
            'Exceptions' => 'Exceptions',
            'Enums' => 'Enums',
            'Policies' => 'Policies',
            'Http.Requests' => 'Requests',
            'Services' => 'Services',
        ];

        foreach ($mappings as $pattern => $replacement) {
            if (str_contains($namespace, $pattern)) {
                return $replacement;
            }
        }

        return $namespace;
    }

    private function createMermaidClass(Class_ $class): MermaidClass
    {
        return new MermaidClass(
            name: $class->name->toString(),
            methods: $this->extractMethods($class),
            properties: $this->extractProperties($class)
        );
    }

    private function extractMethods(Class_ $class): array
    {
        $methods = [];
        foreach ($class->getMethods() as $method) {
            $visibility = $this->getVisibility($method);
            $returnType = $method->returnType ? $this->getTypeName($method->returnType) : 'void';

            $methods[] = new MermaidMethod(
                name: $method->name->toString(),
                visibility: $visibility,
                parameters: $this->extractParameters($method),
                returnType: $returnType
            );
        }

        return $methods;
    }

    private function extractParameters(ClassMethod $method): array
    {
        $parameters = [];
        foreach ($method->params as $param) {
            $parameters[] = new MermaidParameter(
                name: $param->var->name,
                type: $param->type ? $this->getTypeName($param->type) : 'mixed'
            );
        }

        return $parameters;
    }

    private function getTypeName($type): string
    {
        if ($type instanceof Node\NullableType) {
            return '?'.$this->getTypeName($type->type);
        }

        if ($type instanceof Node\UnionType) {
            return implode('|', array_map(
                fn ($subType) => $this->getTypeName($subType),
                $type->types
            ));
        }

        if ($type instanceof Node\IntersectionType) {
            return implode('&', array_map(
                fn ($subType) => $this->getTypeName($subType),
                $type->types
            ));
        }

        if ($type instanceof Node\Name) {
            return $type->toString();
        }

        if ($type instanceof Node\Identifier) {
            return $type->toString();
        }

        // Fallback für andere Typen
        return 'mixed';
    }

    private function getVisibility(Node $node): string
    {
        if ($node->isPublic()) {
            return 'public';
        }
        if ($node->isProtected()) {
            return 'protected';
        }

        return 'private';
    }

    private function extractProperties(Class_ $class): array
    {
        $properties = [];
        foreach ($class->getProperties() as $property) {
            $visibility = $this->getVisibility($property);
            $type = $property->type ? $this->getTypeName($property->type) : 'mixed';

            foreach ($property->props as $prop) {
                $properties[] = new MermaidProperty(
                    name: $prop->name->toString(),
                    visibility: $visibility,
                    type: $type
                );
            }
        }

        return $properties;
    }
}
