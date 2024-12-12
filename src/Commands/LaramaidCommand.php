<?php

namespace Fase22\Laramaid\Commands;

use Illuminate\Console\Command;

class LaramaidCommand extends Command
{
    public $signature = 'laramaid';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
