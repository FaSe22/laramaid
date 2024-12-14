<?php

namespace Fase22\Laramaid\Commands;

use Fase22\Laramaid\Laravel\LaravelClassGenerator;
use Fase22\Laramaid\Mermaid\MermaidParser;
use Illuminate\Console\Command;

class LaramaidCommand extends Command
{
    public $signature = 'laramaid {target_directory} {mermaid_file}';

    public $description = 'Generate Laravel classes from Mermaid class diagram';

    public function handle(
        LaravelClassGenerator $generator
    ): int {
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

        try {
            $content = file_get_contents($mermaidFilePath);
            $parser = new MermaidParser($content);
            $namespaceData = $parser->parse()->getNamespaces();

            $generator->generate($targetDirectory, $namespaceData);

            $this->info('Done!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
