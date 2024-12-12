# A bridge between Laravel and Mermaid. 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fase22/laramaid.svg?style=flat-square)](https://packagist.org/packages/fase22/laramaid)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/fase22/laramaid/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/fase22/laramaid/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/fase22/laramaid/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/fase22/laramaid/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/fase22/laramaid.svg?style=flat-square)](https://packagist.org/packages/fase22/laramaid)

You can go both directions: Either scan your Laravel Application and generate a class diagram from it or pass an class diagram for code generation.

## Generate Class Diagram

Laramaid will scan your Laravel Application and generate a Mermaid Class Diagram for it.
This way your Diagram will always be up to date.

### Github Action

Feel free to integrate the Laramaid Class-Diagram-Generator in your workflow. 
This ways you always get up to date diagrams that are part of version control.

## Generate Laravel Boilerplate Code from your Class Diagram

Laramaid can generate boilerplate code from your mermaid class diagram: models, controllers, policies, enums, etc.
It supports method-stub and property generation.

## TBD: 
Generate migration columns and factories

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laramaid.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laramaid)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require fase22/laramaid
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laramaid-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laramaid-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laramaid-views"
```

## Usage

```php
$laramaid = new Fase22\Laramaid();
echo $laramaid->echoPhrase('Hello, Fase22!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [sebastianfaber](https://github.com/fase22)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
