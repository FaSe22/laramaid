<?php

use Fase22\Laramaid\Laravel\ClassUpdater;
use Fase22\Laramaid\Mermaid\MermaidClass;
use Fase22\Laramaid\Mermaid\MermaidMethod;
use Fase22\Laramaid\Mermaid\MermaidProperty;

test('updater adds properties to existing class', function () {
    $tempDir = sys_get_temp_dir() . '/laramaid_test_' . uniqid();
    mkdir($tempDir);

    $classContent = <<<'PHP'
<?php
namespace App\Models;

class User {
}
PHP;

    file_put_contents($tempDir . '/User.php', $classContent);

    $updater = new ClassUpdater();
    $class = new MermaidClass(
        name: 'User',
        methods: [],
        properties: [
            new MermaidProperty('name', 'public', 'string')
        ]
    );

    $updater->update($tempDir . '/User.php', $class);

    $updatedContent = file_get_contents($tempDir . '/User.php');

    expect($updatedContent)
        ->toContain('public string $name;')
        ->toContain('@var string');

    // Cleanup
    unlink($tempDir . '/User.php');
    rmdir($tempDir);
});

test('updater preserves existing class content', function () {
    $tempDir = sys_get_temp_dir() . '/laramaid_test_' . uniqid();
    mkdir($tempDir);

    $classContent = <<<'PHP'
<?php
namespace App\Models;

class User {
    private int $id;
}
PHP;

    file_put_contents($tempDir . '/User.php', $classContent);

    $updater = new ClassUpdater();
    $class = new MermaidClass(
        name: 'User',
        methods: [
            new MermaidMethod('getName', 'public', [], 'string')
        ],
        properties: []
    );

    $updater->update($tempDir . '/User.php', $class);

    $updatedContent = file_get_contents($tempDir . '/User.php');

    expect($updatedContent)
        ->toContain('private int $id;')
        ->toContain('public function getName()')
        ->toContain('return string');

    // Cleanup
    unlink($tempDir . '/User.php');
    rmdir($tempDir);
});
