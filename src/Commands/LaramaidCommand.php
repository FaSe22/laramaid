<?php

namespace Fase22\Laramaid\Commands;

use Illuminate\Console\Command;

class LaramaidCommand extends Command
{
    public $signature = 'laramaid {target_directory} {mermaid_file}';

    public $description = 'Generate Laravel classes from Mermaid class diagram';

    public function handle(): int
    {
        $targetDirectory = $this->argument('target_directory');
        $mermaidFilePath = $this->argument('mermaid_file');

        if (! file_exists($mermaidFilePath)) {
            $this->error('Error: Mermaid file not found');

            return self::FAILURE;
        }
        if (! is_dir($targetDirectory)) {
            $this->error('Error: Target directory not found');

            return self::FAILURE;
        }

        $content = file_get_contents($mermaidFilePath);

        $content = preg_replace('/%.*$/m', '', $content);
        $content = preg_replace('/\n\s*\n/', "\n", $content);
        $content = preg_replace('/note\s+"[^"]+"/s', '', $content);
        $content = preg_replace('/direction\s+\w+/s', '', $content);

        $pattern = '/namespace\s+(\w+)\s*{((?:[^{}]*|{(?:[^{}]*|{[^{}]*})*})*)}(?=\s*namespace|\s*$)/s';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $namespaces = [];
        $classMethods = [];

        foreach ($matches as $index => $match) {
            $namespaceName = $match[1];
            $namespaceContent = $match[2];

            if (preg_match_all('/class\s+(\w+)(?:\[[^\]]*\])?\s*{([^}]+)}/s', $namespaceContent, $classMatches, PREG_SET_ORDER)) {
                $namespaces[$namespaceName] = [];

                foreach ($classMatches as $classMatch) {
                    $className = $classMatch[1];
                    $classContent = $classMatch[2];

                    $namespaces[$namespaceName][] = $className;

                    // extract methods
                    preg_match_all('/([+-])(\w+)\((.*?)\)(?:\s*:\s*(\w+))?/s', $classContent, $methodMatches, PREG_SET_ORDER);

                    $methods = [];
                    foreach ($methodMatches as $methodMatch) {
                        $visibility = match ($methodMatch[1]) {
                            '+' => 'public',
                            '-' => 'private',
                            '#' => 'protected',
                            default => 'public'
                        };
                        $methodName = $methodMatch[2];
                        $parameters = $methodMatch[3];
                        $returnType = isset($methodMatch[4]) ? $methodMatch[4] : 'void';

                        // mermaid -> php
                        $phpParams = [];
                        if (! empty($parameters)) {
                            $params = explode(',', $parameters);
                            foreach ($params as $param) {
                                $parts = explode(':', trim($param));
                                $paramName = trim($parts[0]);
                                $paramType = isset($parts[1]) ? trim($parts[1]) : 'mixed';
                                $phpParams[] = "$paramType \$$paramName";
                            }
                        }

                        $methods[] = [
                            'visibility' => $visibility,
                            'name' => $methodName,
                            'parameters' => implode(', ', $phpParams),
                            'returnType' => $returnType,
                        ];
                    }

                    $classMethods[$className] = $methods;
                }

                $namespaces[$namespaceName] = array_unique($namespaces[$namespaceName]);
            }
        }

        $namespaceCommands = [
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

        $commandOptions = [
            'make:model' => ' -fm',
        ];

        foreach ($namespaces as $namespace => $classes) {
            if (isset($namespaceCommands[$namespace])) {
                $baseCommand = $namespaceCommands[$namespace];
                $options = isset($commandOptions[$baseCommand]) ? $commandOptions[$baseCommand] : '';

                foreach ($classes as $className) {
                    $command = sprintf('php artisan %s %s%s', $baseCommand, $className, $options);

                    $currentDir = getcwd();
                    chdir($targetDirectory);

                    $this->info("Executing for namespace $namespace: $command");
                    exec($command, $output, $returnVar);

                    if ($returnVar === 0 && isset($classMethods[$className])) {
                        $classPath = $this->determineClassPath($targetDirectory, $namespace, $className);
                        if (file_exists($classPath)) {
                            $this->addMethodsToClass($classPath, $classMethods[$className]);
                        }
                    }

                    if ($output) {
                        $this->line(implode("\n", $output));
                    }

                    chdir($currentDir);

                    if ($returnVar !== 0) {
                        $this->warn("Command failed for $className with return code $returnVar");
                    }
                }
            } else {
                $this->warn("No command defined for namespace: $namespace");
            }
        }

        $this->info('Done!');

        return self::SUCCESS;
    }

    private function determineClassPath($targetDirectory, $namespace, $className)
    {
        $paths = [
            'Controllers' => 'app/Http/Controllers',
            'Models' => 'app/Models',
            'Events' => 'app/Events',
            'Listeners' => 'app/Listeners',
            'Exceptions' => 'app/Exceptions',
            'Enums' => 'app/Enums',
            'Policies' => 'app/Policies',
            'Notifications' => 'app/Notifications',
            'Requests' => 'app/Http/Requests',
        ];

        $basePath = isset($paths[$namespace]) ? $paths[$namespace] : 'app';

        return "$targetDirectory/$basePath/$className.php";
    }

    private function addMethodsToClass($classPath, $methods)
    {
        $content = file_get_contents($classPath);

        $insertPosition = strrpos($content, '}');
        if ($insertPosition === false) {
            return;
        }

        $methodsCode = "\n";
        foreach ($methods as $method) {
            $methodsCode .= sprintf(
                "    /**\n     * %s\n     * @param %s\n     * @return %s\n     */\n    %s function %s(%s): %s\n    {\n        //TODO: Implement %s\n    }\n\n",
                ucfirst($method['name']),
                $method['parameters'],
                $method['returnType'],
                $method['visibility'],
                $method['name'],
                $method['parameters'],
                $method['returnType'],
                $method['name']
            );
        }

        $newContent = substr_replace($content, $methodsCode, $insertPosition, 0);
        file_put_contents($classPath, $newContent);
    }
}
