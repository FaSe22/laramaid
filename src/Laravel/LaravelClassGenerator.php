<?php

namespace Fase22\Laramaid\Laravel;

use Fase22\Laramaid\Mermaid\MermaidClass;

class LaravelClassGenerator
{
    private array $namespaceCommands = [
        'Controllers' => 'make:controller',
        'Models' => 'make:model',
        'Events' => 'make:event',
        'Listeners' => 'make:listener',
        'Exceptions' => 'make:exception',
        'Enums' => 'make:enum',
        'Policies' => 'make:policy',
        'Notifications' => 'make:notification',
        'Requests' => 'make:request',
    ];

    private array $commandOptions = [
        'make:model' => ' -fm',
    ];

    public function __construct(
        private readonly PathResolver $pathResolver,
        private readonly ClassUpdater $classUpdater
    ) {
        $classes = config('laramaid.namespaces');
        foreach ($classes as $key => $value) {
            $this->namespaceCommands[$key] = 'make:class';
            $this->commandOptions['make:class'] = ' --namespace='.$value;
        }
    }

    public function generate(string $targetDirectory, array $namespaceData): void
    {
        foreach ($namespaceData as $namespaceName => $classes) {
            if (! isset($this->namespaceCommands[$namespaceName])) {
                continue;
            }

            $baseCommand = $this->namespaceCommands[$namespaceName];
            $options = $this->commandOptions[$baseCommand] ?? '';

            foreach ($classes as $className => $classData) {
                $this->generateClass($targetDirectory, $namespaceName, $className, $classData, $baseCommand, $options);
            }
        }
    }

    private function generateClass(
        string $targetDirectory,
        string $namespaceName,
        string $className,
        MermaidClass $classData,
        string $baseCommand,
        string $options
    ): void {
        $command = sprintf('php artisan %s %s%s', $baseCommand, $className, $options);

        $currentDir = getcwd();
        chdir($targetDirectory);

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $classPath = $this->pathResolver->resolveClassPath($targetDirectory, $namespaceName, $className);
            if (file_exists($classPath)) {
                $this->classUpdater->update($classPath, $classData);
            }
        }

        chdir($currentDir);
    }
}
