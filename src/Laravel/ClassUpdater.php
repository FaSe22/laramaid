<?php

namespace Fase22\Laramaid\Laravel;

use Fase22\Laramaid\Mermaid\MermaidClass;

class ClassUpdater
{
    public function update(string $classPath, MermaidClass $classData): void
    {
        $content = file_get_contents($classPath);
        $content = $this->addProperties($content, $classData);
        $content = $this->addMethods($content, $classData);
        file_put_contents($classPath, $content);
    }

    private function addProperties(string $content, MermaidClass $classData): string
    {
        if (empty($classData->properties)) {
            return $content;
        }

        if (preg_match('/class\s+\w+(?:\s+extends\s+\w+)?(?:\s+implements\s+[\w,\s]+)?\s*{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1] + strlen($matches[0][0]);

            $propertyCode = "\n";
            foreach ($classData->properties as $property) {
                $propertyCode .= $property->toPhp() . "\n";
            }

            return substr_replace($content, $propertyCode, $insertPosition, 0);
        }

        return $content;
    }

    private function addMethods(string $content, MermaidClass $classData): string
    {
        $insertPosition = strrpos($content, '}');
        if ($insertPosition === false) {
            return $content;
        }

        $methodCode = "\n";
        foreach ($classData->methods as $method) {
            $methodCode .= $method->toPhp() . "\n";
        }

        return substr_replace($content, $methodCode, $insertPosition, 0);
    }
}
