<?php

namespace Fase22\Laramaid\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeServiceClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name} {--namespace=App\\Services}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new service class in a given namespace';

    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $namespace = rtrim($this->option('namespace'), '\\\\');

        $classPath = base_path(str_replace('\\', DIRECTORY_SEPARATOR, $namespace)) . DIRECTORY_SEPARATOR . $name . '.php';

        if ($this->files->exists($classPath)) {
            $this->error("Service class already exists at $classPath");
            return 1;
        }

        $stub = $this->getStub();

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $name],
            $stub
        );

        $this->makeDirectory($classPath);
        $this->files->put($classPath, $content);

        $this->info("Service class $name created successfully at $classPath");
        return 0;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return <<<EOT
<?php

namespace {{ namespace }};

class {{ class }}
{
    //
}
EOT;
    }

    /**
     * Create the directory for the class if it doesn’t exist.
     *
     * @param string $path
     * @return void
     */
    protected function makeDirectory($path)
    {
        $directory = dirname($path);

        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }
}
