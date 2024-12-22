<?php

namespace Fase22\Laramaid\Laravel;

class PathResolver
{
    private array $paths = [
        'Controllers' => 'app/Http/Controllers',
        'Models' => 'app/Models',
        'Events' => 'app/Events',
        'Listeners' => 'app/Listeners',
        'Exceptions' => 'app/Exceptions',
        'Enums' => 'app/Enums',
        'Policies' => 'app/Policies',
        'Notifications' => 'app/Notifications',
        'Requests' => 'app/Http/Requests',
        'Services' => 'app/Services',
    ];

    public function resolveClassPath(string $targetDirectory, string $namespace, string $className): string
    {
        $basePath = $this->paths[$namespace] ?? 'app';

        return "$targetDirectory/$basePath/$className.php";
    }
}
