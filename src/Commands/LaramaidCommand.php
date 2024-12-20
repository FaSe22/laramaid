<?php

namespace Fase22\Laramaid\Commands;

use Fase22\Laramaid\Json\Rehydrator;
use Fase22\Laramaid\Laravel\LaravelClassGenerator;
use Fase22\Laramaid\Mermaid\MermaidParser;
use Illuminate\Console\Command;

class LaramaidCommand extends Command
{
    public $signature = 'laramaid {mermaid_file}';

    public $description = 'Generate Laravel classes from Mermaid class diagram';

    /**
     * Execute the console command.
     * This command reads a Mermaid class diagram and generates corresponding Laravel classes.
     *
     * The process involves:
     * 1. Reading and parsing the Mermaid file
     * 2. Extracting namespace and class information
     * 3. Generating Laravel classes with proper structure
     *
     * @param  LaravelClassGenerator  $generator  Service for generating Laravel classes
     * @return int Command exit code (SUCCESS or FAILURE)
     *
     * @throws \Exception If file reading or parsing fails
     */
    public function handle(
        LaravelClassGenerator $generator
    ): int {
        $mermaidFilePath = $this->argument('mermaid_file');

        if (! file_exists($mermaidFilePath)) {
            $this->error('Error: Mermaid file not found');

            return self::FAILURE;
        }

        try {
            $content = file_get_contents($mermaidFilePath);
            $parser = new MermaidParser($content);
            $namespaceData = $parser->parse()->getNamespaces();

            if (config('laramaid.generate-json')) {
                file_put_contents('laramaid.json', json_encode($namespaceData));
            }

            $generator->generate('./', $namespaceData);

            $this->info('Done!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
