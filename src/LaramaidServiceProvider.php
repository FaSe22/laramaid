<?php

namespace Fase22\Laramaid;

use Fase22\Laramaid\Commands\GenerateMermaidDiagram;
use Fase22\Laramaid\Commands\LaramaidCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaramaidServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laramaid')
            ->hasConfigFile('laramaid')
            ->hasViews()
            ->hasMigration('create_laramaid_table')
            ->hasCommand(LaramaidCommand::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LaramaidCommand::class,
                GenerateMermaidDiagram::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/laramaid.php' => config_path('laramaid.php'),
        ]);
    }
}
