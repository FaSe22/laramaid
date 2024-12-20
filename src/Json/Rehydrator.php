<?php

namespace Fase22\Laramaid\Json;

use Fase22\Laramaid\Mermaid\MermaidClass;
use Fase22\Laramaid\Mermaid\MermaidMethod;
use Fase22\Laramaid\Mermaid\MermaidParameter;
use Fase22\Laramaid\Mermaid\MermaidProperty;

class Rehydrator
{

    public static function rehydrate(array $data): array
    {
        $result = [];

        foreach ($data as $category => $items) {
            $result[$category] = array_map(
                fn ($item) => self::rehydrateMermaidClass($item),
                $items
            );
        }

        return $result;
    }
    public static function rehydrateMermaidClass(array $data): MermaidClass
    {
        $methods = array_map(fn ($method) => self::rehydrateMermaidMethod($method), $data['methods'] ?? []);
        $properties = array_map(fn ($property) => self::rehydrateMermaidProperty($property), $data['properties'] ?? []);

        return new MermaidClass(
            $data['name'],
            $methods,
            $properties
        );
    }

    private static function rehydrateMermaidMethod(array $data): MermaidMethod
    {
        $parameters = array_map(fn ($param) => self::rehydrateMermaidParameter($param), $data['parameters'] ?? []);

        return new MermaidMethod(
            $data['name'],
            $data['visibility'],
            $parameters,
            $data['returnType']
        );
    }

    private static function rehydrateMermaidParameter(array $data): MermaidParameter
    {
        return new MermaidParameter(
            $data['name'],
            $data['type']
        );
    }

    private static function rehydrateMermaidProperty(array $data): MermaidProperty
    {
        return new MermaidProperty(
            $data['name'],
            $data['visibility'],
            $data['type']
        );
    }
}
