<?php

namespace Fase22\Laramaid\Commands;

use Fase22\Laramaid\Json\Rehydrator;
use Fase22\Laramaid\Laravel\LaravelClassGenerator;
use Illuminate\Console\Command;

class GenerateFromJson extends Command
{
    public $signature = 'laramaid:json {json_file}';

    public $description = 'Generate Laravel classes from Mermaid class diagram';

    public function handle(
        LaravelClassGenerator $generator,
        Rehydrator $rehydrator
    ): int {
        $jsonFilePath = $this->argument('json_file');

        if (! file_exists($jsonFilePath)) {
            $this->error('Error: Json file not found');

            return self::FAILURE;
        }

        try {
            $json = json_decode(file_get_contents($jsonFilePath), true);
            $namespaceData = $rehydrator->rehydrate($json);

            $generator->generate('./', $namespaceData);

            $this->info('Done!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
