<?php

namespace Fase22\Laramaid\Mermaid;

class MermaidParser
{
    private string $content;

    private array $namespaces = [];

    public function __construct(string $content)
    {
        $this->content = $this->cleanContent($content);
    }

    public function parse(): self
    {
        $this->parseNamespaces();

        return $this;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Clean the mermaid content by removing:
     * - Comments (starting with %)
     * - Empty lines
     * - Note blocks
     * - Direction statements
     */
    private function cleanContent(string $content): string
    {
        $content = preg_replace('/%.*$/m', '', $content);
        $content = preg_replace('/\n\s*\n/', "\n", $content);
        $content = preg_replace('/note\s+"[^"]+"/s', '', $content);
        $content = preg_replace('/direction\s+\w+/s', '', $content);

        return $content;
    }

    /**
     * Parse namespace blocks from the mermaid content
     *
     * Regex explanation:
     * namespace\s+(\w+)\s*{        - Match namespace keyword, name and opening brace
     * ((?:[^{}]*|                  - Start capture group for content, match non-brace characters
     * {(?:[^{}]*|{[^{}]*})*})*)}  - Or match nested braces with their content recursively
     * (?=\s*namespace|\s*$)        - Lookahead for next namespace or end of string
     */
    private function parseNamespaces(): void
    {
        $pattern = '/namespace\s+(\w+)\s*{((?:[^{}]*|{(?:[^{}]*|{[^{}]*})*})*)}(?=\s*namespace|\s*$)/s';
        preg_match_all($pattern, $this->content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $namespaceName = $match[1];
            $namespaceContent = $match[2];

            $this->namespaces[$namespaceName] = $this->parseClasses($namespaceContent);
        }
    }

    private function parseClasses(string $namespaceContent): array
    {
        $classes = [];
        if (preg_match_all('/class\s+(\w+)(?:\[[^\]]*\])?\s*{([^}]+)}/s', $namespaceContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $className = $match[1];
                $classContent = $match[2];

                $classes[$className] = new MermaidClass(
                    $className,
                    $this->parseMethods($classContent),
                    $this->parseProperties($classContent)
                );
            }
        }

        return $classes;
    }

    /**
     * Parse class definitions within a namespace block
     *
     * Regex explanation:
     * class\s+(\w+)               - Match class keyword and capture class name
     * (?:\[[^\]]*\])?            - Optional description in square brackets
     * \s*{([^}]+)}               - Match class content between braces
     */
    private function parseMethods(string $classContent): array
    {
        $methods = [];
        preg_match_all('/([+-])(\w+)\((.*?)\)(?:\s*:\s*(\w+))?/s', $classContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methods[] = new MermaidMethod(
                name: $match[2],
                visibility: $this->parseVisibility($match[1]),
                parameters: $this->parseParameters($match[3]),
                returnType: $match[4] ?? 'void'
            );
        }

        return $methods;
    }

    /**
     * Parse method definitions from class content
     *
     * Regex explanation:
     * ([+-])                      - Capture visibility symbol (+ or -)
     * (\w+)                       - Capture method name
     * \((.*?)\)                   - Capture parameter list
     * (?:\s*:\s*(\w+))?          - Optional return type after colon
     */
    private function parseProperties(string $classContent): array
    {
        $properties = [];
        preg_match_all('/([+-])(\w+):\s*(\w+)/', $classContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $properties[] = new MermaidProperty(
                name: $match[2],
                visibility: $this->parseVisibility($match[1]),
                type: $match[3]
            );
        }

        return $properties;
    }

    /**
     * Parse property definitions from class content
     *
     * Regex explanation:
     * ([+-])                      - Capture visibility symbol
     * (\w+)                       - Capture property name
     * :\s*(\w+)                   - Capture type after colon
     */
    private function parseVisibility(string $symbol): string
    {
        return match ($symbol) {
            '+' => 'public',
            '-' => 'private',
            '#' => 'protected',
            default => 'public'
        };
    }

    private function parseParameters(string $parameters): array
    {
        if (empty($parameters)) {
            return [];
        }

        $params = [];
        foreach (explode(',', $parameters) as $param) {
            $parts = explode(':', trim($param));
            $params[] = new MermaidParameter(
                name: trim($parts[0]),
                type: isset($parts[1]) ? trim($parts[1]) : 'mixed'
            );
        }

        return $params;
    }
}
