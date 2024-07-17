# Slim Framework Twig View

[![Latest Version on Packagist](https://img.shields.io/github/release/slimphp/twig-view.svg)](https://packagist.org/packages/slim/Twig-View)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Build Status](https://github.com/slimphp/Twig-View/actions/workflows/tests.yml/badge.svg?branch=3.x)](https://github.com/slimphp/Twig-View/actions)
[![Coverage Status](https://coveralls.io/repos/github/slimphp/Twig-View/badge.svg?branch=3.x)](https://coveralls.io/github/slimphp/Twig-View?branch=3.x)
[![Total Downloads](https://img.shields.io/packagist/dt/slim/Twig-View.svg)](https://packagist.org/packages/slim/Twig-View/stats)

This is a Slim Framework view helper built on top of the Twig templating component. You can use this component to create and render templates in your Slim Framework application.

## Install

Via [Composer](https://getcomposer.org/)

```bash
composer require slim/twig-view
```

Requires Slim Framework 4, Twig 3 and PHP 7.4 or newer.

## Usage

### With DI Container

```php
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Create Container
$container = new Container();

// Set view in Container
$container->set(Twig::class, function() {
    return Twig::create(__DIR__ . '/../templates', ['cache' => 'path/to/cache']);
});

// Create App from container
$app = AppFactory::createFromContainer($container);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

// Add other middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Render from template file templates/profile.html.twig
$app->get('/hello/{name}', function ($request, $response, $args) {
    $viewData = [
        'name' => $args['name'],
    ];

    $twig = $this->get(Twig::class);

    return $twig->render($response, 'profile.html.twig', $viewData);
})->setName('profile');

// Render from string
$app->get('/hi/{name}', function ($request, $response, $args) {
    $viewData = [
        'name' => $args['name'],
    ];

    $twig = $this->get(Twig::class);
    $str = $twig->fetchFromString('<p>Hi, my name is {{ name }}.</p>', $viewData);
    $response->getBody()->write($str);

    return $response;
});

// Run app
$app->run();
```

### Without DI container

```php
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Create App
$app = AppFactory::create();

// Create Twig
$twig = Twig::create('path/to/templates', ['cache' => 'path/to/cache']);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Define named route
$app->get('/hello/{name}', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'profile.html.twig', [
        'name' => $args['name']
    ]);
})->setName('profile');

// Render from string
$app->get('/hi/{name}', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    $str = $view->fetchFromString(
        '<p>Hi, my name is {{ name }}.</p>',
        [
            'name' => $args['name']
        ]
    );
    $response->getBody()->write($str);

    return $response;
});

// Run app
$app->run();
```

## Custom template functions

`TwigExtension` provides these functions to your Twig templates:

* `url_for()` - returns the URL for a given route. e.g.: /hello/world
* `full_url_for()` - returns the URL for a given route. e.g.: https://www.example.com/hello/world
* `is_current_url()` - returns true is the provided route name and parameters are valid for the current path.
* `current_url()` - returns the current path, with or without the query string.
* `get_uri()` - returns the `UriInterface` object from the incoming `ServerRequestInterface` object
* `base_path()` - returns the base path.

You can use `url_for` to generate complete URLs to any Slim application named route and use `is_current_url` to determine if you need to mark a link as active as shown in this example Twig template:

```html
<h1>User List</h1>
<ul>
    <li><a href="{{ url_for('profile', { 'name': 'josh' }) }}" {% if is_current_url('profile', { 'name': 'josh' }) %}class="active"{% endif %}>Josh</a></li>
    <li><a href="{{ url_for('profile', { 'name': 'andrew' }) }}">Andrew</a></li>
</ul>
```

## Tests

To execute the test suite, you'll need to clone the repository and install the dependencies.

```bash
$ git clone https://github.com/slimphp/Twig-View
$ composer install
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@slimframework.com instead of using the issue tracker.

## Credits

- [Josh Lockhart](https://github.com/codeguy)
- [Pierre Bérubé](https://github.com/l0gicgate)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
