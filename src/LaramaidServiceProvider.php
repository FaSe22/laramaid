<?php

namespace Fase22\Laramaid;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laramaid_table')
            ->hasCommand(LaramaidCommand::class);
    }
}
