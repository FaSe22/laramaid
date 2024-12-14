<?php

namespace Fase22\Laramaid\Laravel;

use Fase22\Laramaid\Mermaid\MermaidClass;
use Fase22\Laramaid\Mermaid\MermaidMethod;
use Fase22\Laramaid\Mermaid\MermaidProperty;
use PhpParser\BuilderFactory;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter;

class ClassUpdater
{
    private $parser;

    private $printer;

    private $factory;

    private $nodeFinder;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForVersion(PhpVersion::getHostVersion());
        $this->printer = new PrettyPrinter\Standard;
        $this->factory = new BuilderFactory;
        $this->nodeFinder = new NodeFinder;
    }

    public function update(string $classPath, MermaidClass $classData): void
    {
        $code = file_get_contents($classPath);
        $ast = $this->parser->parse($code);

        if (! $ast) {
            throw new \RuntimeException('Failed to parse the class file.');
        }

        // Finde die Klassen-Node
        /** @var Class_ $classNode */
        $classNode = $this->nodeFinder->findFirst($ast, function (Node $node) {
            return $node instanceof Class_;
        });

        if (! $classNode) {
            throw new \RuntimeException('No class definition found.');
        }

        // Füge Properties hinzu
        foreach ($classData->properties as $property) {
            $classNode->stmts[] = $this->createProperty($property);
        }

        // Füge Methoden hinzu
        foreach ($classData->methods as $method) {
            $classNode->stmts[] = $this->createMethod($method);
        }

        // Generiere den neuen Code
        $newCode = $this->printer->prettyPrintFile($ast);
        file_put_contents($classPath, $newCode);
    }

    private function createProperty(MermaidProperty $property): Node\Stmt\Property
    {
        $flags = $this->getVisibilityFlag($property->visibility);

        return $this->factory->property($property->name)
            ->setDocComment($this->createPropertyDocBlock($property))
            ->makePublic()
            ->setType($property->type)
            ->getNode();
    }

    private function createMethod(MermaidMethod $method): Node\Stmt\ClassMethod
    {
        $methodBuilder = $this->factory->method($method->name)
            ->setDocComment($this->createMethodDocBlock($method));

        // Setze Visibility
        switch ($method->visibility) {
            case 'private':
                $methodBuilder->makePrivate();
                break;
            case 'protected':
                $methodBuilder->makeProtected();
                break;
            default:
                $methodBuilder->makePublic();
        }

        // Füge Parameter hinzu
        foreach ($method->parameters as $param) {
            $methodBuilder->addParam(
                $this->factory->param($param->name)
                    ->setType($param->type)
                    ->getNode()
            );
        }

        // Setze Return Type
        $methodBuilder->setReturnType($method->returnType);

        // Füge TODO-Kommentar im Body hinzu
        $methodBuilder->addStmt(
            new Node\Stmt\Nop([
                'comments' => [new \PhpParser\Comment\Doc("// TODO: Implement {$method->name}")],
            ])
        );

        return $methodBuilder->getNode();
    }

    private function createPropertyDocBlock(MermaidProperty $property): string
    {
        return "/**\n * @var {$property->type}\n */";
    }

    private function createMethodDocBlock(MermaidMethod $method): string
    {
        $paramDocs = array_map(
            fn ($param) => " * @param {$param->type} \${$param->name}",
            $method->parameters
        );

        $docs = [
            '/**',
            ' * '.ucfirst($method->name),
        ];

        if (! empty($paramDocs)) {
            $docs = array_merge($docs, $paramDocs);
        }

        $docs[] = " * @return {$method->returnType}";
        $docs[] = ' */';

        return implode("\n", $docs);
    }

    private function getVisibilityFlag(string $visibility): int
    {
        return match ($visibility) {
            'private' => Modifiers::PRIVATE,
            'protected' => Modifiers::PROTECTED,
            default => Modifiers::PUBLIC
        };
    }
}
