<?php

namespace Fase22\Laramaid\Commands;

use Illuminate\Console\Command;
use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node;
use PhpParser\Node\Stmt\{Class_, ClassMethod, Property, Namespace_};
use PhpParser\PhpVersion;

class GenerateMermaidDiagram extends Command
{
    public $signature = 'laramaid:extract
        {target_directory? : Directory to analyze (defaults to app/)}
        {--output= : Output file for the mermaid diagram}';

    public $description = 'Extract a Mermaid class diagram from Laravel application';

    private $parser;
    private $nodeFinder;

    public function __construct()
    {
        parent::__construct();
        $this->parser = (new ParserFactory)->createForVersion(PhpVersion::getHostVersion());
        $this->nodeFinder = new NodeFinder;
    }

    public function handle(): int
    {
        $targetDirectory = $this->argument('target_directory') ?? app_path();
        $outputFile = $this->option('output');

        if (!is_dir($targetDirectory)) {
            $this->error("Directory not found: $targetDirectory");
            return self::FAILURE;
        }

        try {
            $mermaidContent = $this->generateMermaidDiagram($targetDirectory);

            if ($outputFile) {
                file_put_contents($outputFile, $mermaidContent);
                $this->info("Mermaid diagram written to: $outputFile");
            } else {
                $this->line($mermaidContent);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function generateMermaidDiagram(string $directory): string
    {
        $files = $this->findPhpFiles($directory);
        $namespaces = $this->analyzeFiles($files);

        return $this->buildMermaidDiagram($namespaces);
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

            // Finde Namespace
            $namespace = $this->nodeFinder->findFirst($ast, function (Node $node) {
                return $node instanceof Namespace_;
            });

            if (!$namespace) continue;

            $namespaceName = $this->getNamespaceName($namespace);
            if (!isset($namespaces[$namespaceName])) {
                $namespaces[$namespaceName] = [];
            }

            // Finde Klassen im Namespace
            $classes = $this->nodeFinder->findInstanceOf($namespace, Class_::class);
            foreach ($classes as $class) {
                $namespaces[$namespaceName][] = $this->analyzeClass($class);
            }
        }

        return $namespaces;
    }

    private function getNamespaceName(Namespace_ $namespace): string
    {
        // Konvertiere den Name-Node in einen String und ersetze Backslashes mit Punkten
        $fullName = str_replace('\\', '.', $namespace->name->toString());

        // Konvertiere den vollen Namespace-Namen in unsere vereinfachte Form
        if (str_contains($fullName, 'Http.Controllers')) return 'Controllers';
        if (str_contains($fullName, 'Models')) return 'Models';
        if (str_contains($fullName, 'Events')) return 'Events';
        if (str_contains($fullName, 'Listeners')) return 'Listeners';
        if (str_contains($fullName, 'Exceptions')) return 'Exceptions';
        if (str_contains($fullName, 'Enums')) return 'Enums';
        if (str_contains($fullName, 'Policies')) return 'Policies';
        if (str_contains($fullName, 'Http.Requests')) return 'Requests';

        return $fullName;
    }

    private function analyzeClass(Class_ $class): array
    {
        return [
            'name' => $class->name->toString(),
            'properties' => $this->extractProperties($class),
            'methods' => $this->extractMethods($class),
        ];
    }

    private function extractProperties(Class_ $class): array
    {
        $properties = [];

        foreach ($class->getProperties() as $property) {
            $visibility = $this->getVisibilitySymbol($property);
            $name = $property->props[0]->name->toString();
            $type = $property->type ? $this->getTypeName($property->type) : 'mixed';

            $properties[] = [
                'visibility' => $visibility,
                'name' => $name,
                'type' => $type,
            ];
        }

        return $properties;
    }

    private function extractMethods(Class_ $class): array
    {
        $methods = [];

        foreach ($class->getMethods() as $method) {
            if ($method->isPrivate()) continue; // Optional: Skip private methods

            $visibility = $this->getVisibilitySymbol($method);
            $parameters = $this->extractParameters($method);
            $returnType = $method->returnType ? $this->getTypeName($method->returnType) : 'void';

            $methods[] = [
                'visibility' => $visibility,
                'name' => $method->name->toString(),
                'parameters' => $parameters,
                'returnType' => $returnType,
            ];
        }

        return $methods;
    }

    private function extractParameters(ClassMethod $method): array
    {
        $parameters = [];

        foreach ($method->params as $param) {
            $parameters[] = [
                'name' => $param->var->name,
                'type' => $param->type ? $this->getTypeName($param->type) : 'mixed',
            ];
        }

        return $parameters;
    }

    private function getVisibilitySymbol(Node $node): string
    {
        if ($node->isPublic()) return '+';
        if ($node->isProtected()) return '#';
        return '-';
    }

    private function getTypeName($type): string
    {
        if ($type instanceof Node\NullableType) {
            return '?' . $this->getTypeName($type->type);
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

    private function buildMermaidDiagram(array $namespaces): string
    {
        $diagram = ["classDiagram\n"];

        foreach ($namespaces as $namespaceName => $classes) {
            // Überspringe leere Namespaces
            if (empty($classes)) {
                continue;
            }

            $diagram[] = "namespace $namespaceName {";

            foreach ($classes as $class) {
                $diagram[] = $this->formatClass($class);
            }

            $diagram[] = "}\n";
        }

        return implode("\n", $diagram);
    }

    private function formatClass(array $class): string
    {
        $lines = ["    class {$class['name']} {"];

        // Properties
        foreach ($class['properties'] as $prop) {
            $lines[] = "        {$prop['visibility']}{$prop['name']}: {$prop['type']}";
        }

        // Methods
        foreach ($class['methods'] as $method) {
            $params = array_map(
                fn ($p) => "{$p['name']}: {$p['type']}",
                $method['parameters']
            );
            $paramString = implode(', ', $params);
            $lines[] = "        {$method['visibility']}{$method['name']}($paramString): {$method['returnType']}";
        }

        $lines[] = "    }";
        return implode("\n", $lines);
    }
}
