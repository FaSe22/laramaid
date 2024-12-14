<?php

use Fase22\Laramaid\Mermaid\MermaidParser;

test('it can parse empty namespaces', function () {
    $content = "namespace Models {}";
    $parser = new MermaidParser($content);

    $namespaces = $parser->parse()->getNamespaces();

    expect($namespaces)->toHaveKey('Models')
        ->and($namespaces['Models'])->toBeEmpty();
});

test('it can parse simple class', function () {
    $content = <<<EOT
namespace Models {
    class User {
        +name: string
        +email: string
        +getFullName(): string
    }
}
EOT;

    $parser = new MermaidParser($content);
    $namespaces = $parser->parse()->getNamespaces();
    $userClass = $namespaces['Models']['User'];

    expect($namespaces)->toHaveKey('Models')
        ->and($namespaces['Models'])->toHaveKey('User')
        ->and($userClass->name)->toBe('User');

    // Test Properties
    expect($userClass->properties)->toHaveCount(2)
        ->and($userClass->properties[0]->name)->toBe('name')
        ->and($userClass->properties[0]->type)->toBe('string')
        ->and($userClass->properties[0]->visibility)->toBe('public');

    // Test Methods
    expect($userClass->methods)->toHaveCount(1)
        ->and($userClass->methods[0]->name)->toBe('getFullName')
        ->and($userClass->methods[0]->returnType)->toBe('string')
        ->and($userClass->methods[0]->visibility)->toBe('public');
});

test('it can parse class with method parameters', function () {
    $content = <<<EOT
namespace Controllers {
    class UserController {
        +store(request: Request): Response
        -validateInput(data: array): bool
    }
}
EOT;

    $parser = new MermaidParser($content);
    $namespaces = $parser->parse()->getNamespaces();
    $controller = $namespaces['Controllers']['UserController'];

    expect($controller->methods)->toHaveCount(2);

    // Test public method
    $storeMethod = $controller->methods[0];
    expect($storeMethod->name)->toBe('store')
        ->and($storeMethod->visibility)->toBe('public')
        ->and($storeMethod->returnType)->toBe('Response')
        ->and($storeMethod->parameters)->toHaveCount(1)
        ->and($storeMethod->parameters[0]->name)->toBe('request')
        ->and($storeMethod->parameters[0]->type)->toBe('Request');

    // Test private method
    $validateMethod = $controller->methods[1];
    expect($validateMethod->name)->toBe('validateInput')
        ->and($validateMethod->visibility)->toBe('private')
        ->and($validateMethod->returnType)->toBe('bool');
});

test('it can parse multiple namespaces', function () {
    $content = <<<EOT
namespace Models {
    class User {
        +id: int
    }
}

namespace Controllers {
    class UserController {
        +index(): Response
    }
}
EOT;

    $parser = new MermaidParser($content);
    $namespaces = $parser->parse()->getNamespaces();

    expect($namespaces)->toHaveCount(2)
        ->toHaveKey('Models')
        ->toHaveKey('Controllers');
});

test('it correctly handles class descriptions', function () {
    $content = <<<EOT
namespace Models {
    class User["User model with authentication"] {
        +id: int
    }
}
EOT;

    $parser = new MermaidParser($content);
    $namespaces = $parser->parse()->getNamespaces();

    expect($namespaces['Models'])->toHaveKey('User');
});

test('it cleans mermaid specific content', function () {
    $content = <<<EOT
%% This is a comment
direction LR
note "This is a note" as N1

namespace Models {
    class User {
        +id: int
    }
}
EOT;

    $parser = new MermaidParser($content);
    $namespaces = $parser->parse()->getNamespaces();

    expect($namespaces)->toHaveKey('Models')
        ->and($namespaces['Models'])->toHaveKey('User');
});

test('it handles methods without return type', function () {
    $content = <<<EOT
namespace Models {
    class User {
        +save()
    }
}
EOT;

    $parser = new MermaidParser($content);
    $namespaces = $parser->parse()->getNamespaces();

    expect($namespaces['Models']['User']->methods[0]->returnType)->toBe('void');
});

test('it handles methods with multiple parameters', function () {
    $content = <<<EOT
namespace Services {
    class UserService {
        +createUser(name: string, email: string, age: int): User
    }
}
EOT;

    $parser = new MermaidParser($content);
    $namespaces = $parser->parse()->getNamespaces();
    $method = $namespaces['Services']['UserService']->methods[0];

    expect($method->parameters)->toHaveCount(3)
        ->and($method->parameters[0]->name)->toBe('name')
        ->and($method->parameters[0]->type)->toBe('string')
        ->and($method->parameters[2]->name)->toBe('age')
        ->and($method->parameters[2]->type)->toBe('int');
});
