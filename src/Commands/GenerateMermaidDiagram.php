<?php

namespace Fase22\Laramaid\Commands;

use Fase22\Laramaid\Laravel\LaravelClassExtractor;
use Fase22\Laramaid\Mermaid\MermaidGenerator;
use Illuminate\Console\Command;

class GenerateMermaidDiagram extends Command
{
    public $signature = 'laramaid:extract
        {target_directory? : Directory to analyze (defaults to app/)}
        {--output= : Output file for the mermaid diagram}';

    public $description = 'Extract a Mermaid class diagram from Laravel application';

    public function handle(
        LaravelClassExtractor $extractor,
        MermaidGenerator $generator
    ): int {
        $targetDirectory = $this->argument('target_directory') ?? app_path();
        $outputFile = $this->option('output');

        if (!is_dir($targetDirectory)) {
            $this->error("Directory not found: $targetDirectory");
            return self::FAILURE;
        }

        try {
            $namespaces = $extractor->extractFromDirectory($targetDirectory);
            $mermaidContent = $generator->generate($namespaces);

            if ($outputFile) {
                file_put_contents($outputFile, $mermaidContent);
                $this->info("Mermaid diagram written to: $outputFile");
            } else {
                $this->line($mermaidContent);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
