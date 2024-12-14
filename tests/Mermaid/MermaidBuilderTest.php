<?php

use Fase22\Laramaid\Mermaid\MermaidClass;
use Fase22\Laramaid\Mermaid\MermaidDiagramBuilder;
use Fase22\Laramaid\Mermaid\MermaidMethod;
use Fase22\Laramaid\Mermaid\MermaidParameter;
use Fase22\Laramaid\Mermaid\MermaidProperty;

test('builder creates valid mermaid syntax', function () {
    $builder = new MermaidDiagramBuilder();

    $class = new MermaidClass(
        name: 'User',
        methods: [
            new MermaidMethod(
                name: 'getName',
                visibility: 'public',
                parameters: [],
                returnType: 'string'
            )
        ],
        properties: [
            new MermaidProperty(
                name: 'name',
                visibility: 'private',
                type: 'string'
            )
        ]
    );

    $namespaces = ['Models' => ['User' => $class]];

    $diagram = $builder->build($namespaces);

    expect($diagram)
        ->toContain('classDiagram')
        ->toContain('namespace Models {')
        ->toContain('class User {')
        ->toContain('-name: string')
        ->toContain('+getName(): string');
});

test('builder handles empty namespaces', function () {
    $builder = new MermaidDiagramBuilder();
    $diagram = $builder->build([]);

    expect($diagram)->toBe("classDiagram\n");
});

test('builder correctly formats method parameters', function () {
    $builder = new MermaidDiagramBuilder();

    $class = new MermaidClass(
        name: 'UserController',
        methods: [
            new MermaidMethod(
                name: 'store',
                visibility: 'public',
                parameters: [
                    new MermaidParameter('request', 'Request'),
                    new MermaidParameter('valid', 'bool')
                ],
                returnType: 'Response'
            )
        ],
        properties: []
    );

    $namespaces = ['Controllers' => ['UserController' => $class]];

    $diagram = $builder->build($namespaces);

    expect($diagram)->toContain('+store(request: Request, valid: bool): Response');
});
