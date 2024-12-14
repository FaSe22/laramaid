<?php

namespace Fase22\Laramaid\Commands;

use Fase22\Laramaid\Mermaid\MermaidClass;
use Fase22\Laramaid\Mermaid\MermaidParser;
use Illuminate\Console\Command;

class LaramaidCommand extends Command
{
    public $signature = 'laramaid {target_directory} {mermaid_file}';

    public $description = 'Generate Laravel classes from Mermaid class diagram';

    public function handle(): int
    {
        $targetDirectory = $this->argument('target_directory');
        $mermaidFilePath = $this->argument('mermaid_file');

        if (!file_exists($mermaidFilePath)) {
            $this->error("Error: Mermaid file not found");
            return self::FAILURE;
        }
        if (!is_dir($targetDirectory)) {
            $this->error("Error: Target directory not found");
            return self::FAILURE;
        }

        $content = file_get_contents($mermaidFilePath);
        $parser = new MermaidParser($content);
        $namespaceData = $parser->parse()->getNamespaces();

        $namespaceCommands = [
            'Controllers' => 'make:controller',
            'Models' => 'make:model',
            'Events' => 'make:event',
            'Listeners' => 'make:listener',
            'Exceptions' => 'make:exception',
            'Enums' => 'make:enum',
            'Policies' => 'make:policy',
            'Notifications' => 'make:notification',
            'Requests' => 'make:request'
        ];

        $commandOptions = [
            'make:model' => ' -fm'
        ];

        foreach ($namespaceData as $namespaceName => $classes) {
            if (!isset($namespaceCommands[$namespaceName])) {
                $this->warn("No command defined for namespace: $namespaceName");
                continue;
            }

            $baseCommand = $namespaceCommands[$namespaceName];
            $options = $commandOptions[$baseCommand] ?? '';

            foreach ($classes as $className => $classData) {
                /** @var MermaidClass $classData */
                $command = sprintf('php artisan %s %s%s', $baseCommand, $className, $options);

                $currentDir = getcwd();
                chdir($targetDirectory);

                $this->info("Executing for namespace $namespaceName: $command");
                exec($command, $output, $returnVar);

                if ($returnVar === 0) {
                    $classPath = $this->determineClassPath($targetDirectory, $namespaceName, $className);
                    if (file_exists($classPath)) {
                        $this->updateClass($classPath, $classData);
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
        }

        $this->info('Done!');
        return self::SUCCESS;
    }

    private function updateClass(string $classPath, MermaidClass $classData): void
    {
        $content = file_get_contents($classPath);

        // Füge Properties hinzu
        if (!empty($classData->properties)) {
            if (preg_match('/class\s+\w+(?:\s+extends\s+\w+)?(?:\s+implements\s+[\w,\s]+)?\s*{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPosition = $matches[0][1] + strlen($matches[0][0]);

                $propertyCode = "\n";
                foreach ($classData->properties as $property) {
                    /** @var MermaidProperty $property */
                    $propertyCode .= $property->toPhp() . "\n";
                }

                $content = substr_replace($content, $propertyCode, $insertPosition, 0);
            }
        }

        // Füge Methoden hinzu
        $insertPosition = strrpos($content, '}');
        if ($insertPosition !== false) {
            $methodCode = "\n";
            foreach ($classData->methods as $method) {
                /** @var MermaidMethod $method */
                $methodCode .= $method->toPhp() . "\n";
            }

            $content = substr_replace($content, $methodCode, $insertPosition, 0);
        }

        file_put_contents($classPath, $content);
    }

    private function determineClassPath($targetDirectory, $namespace, $className): string
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
            'Requests' => 'app/Http/Requests'
        ];

        $basePath = $paths[$namespace] ?? 'app';
        return "$targetDirectory/$basePath/$className.php";
    }
}
