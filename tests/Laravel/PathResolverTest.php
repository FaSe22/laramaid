<?php

use Fase22\Laramaid\Laravel\PathResolver;

test('resolver returns correct paths for different namespaces', function () {
    $resolver = new PathResolver();
    $baseDir = '/app';

    expect($resolver->resolveClassPath($baseDir, 'Controllers', 'UserController'))
        ->toBe('/app/app/Http/Controllers/UserController.php')
        ->and($resolver->resolveClassPath($baseDir, 'Models', 'User'))
        ->toBe('/app/app/Models/User.php')
        ->and($resolver->resolveClassPath($baseDir, 'Events', 'UserCreated'))
        ->toBe('/app/app/Events/UserCreated.php');
});
