# Slim Framework Twig View

[![Build Status](https://travis-ci.org/slimphp/Twig-View.svg?branch=master)](https://travis-ci.org/slimphp/Twig-View)
[![Coverage Status](https://coveralls.io/repos/github/slimphp/Twig-View/badge.svg?branch=3.x)](https://coveralls.io/github/slimphp/Twig-View?branch=3.x)
[![License](https://poser.pugx.org/slim/twig-view/license)](https://packagist.org/packages/slim/twig-view)

This is a Slim Framework view helper built on top of the Twig templating component. You can use this component to create and render templates in your Slim Framework application. It works with Twig 2 and PHP 7.1 or newer.

## Install

Via [Composer](https://getcomposer.org/)

```bash
$ composer require slim/twig-view:3.0.0-beta
```

Requires Slim Framework 4 and PHP 7.1 or newer.

## Usage

```php
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/vendor/autoload.php';

// Create Container
$container = new Container();
AppFactory::setContainer($container);

// Set view in Container
$container->set('view', function() {
    return new Twig('path/to/templates', ['cache' => 'path/to/cache']);
});

// Create App
$app = new AppFactory::create();

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

// Define named route
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'profile.html', [
        'name' => $args['name']
    ]);
})->setName('profile');

// Render from string
$app->get('/hi/{name}', function ($request, $response, $args) {
    $str = $this->get('view')->fetchFromString(
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

### Without container

```php
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/vendor/autoload.php';

// Create App
$app = new AppFactory::create();

// Create Twig
$twig = Twig::create('path/to/templates', ['cache' => 'path/to/cache']);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Define named route
$app->get('/hello/{name}', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'profile.html', [
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
* `full_url_for()` - returns the URL for a given route. e.g.: http://www.example.com/hello/world
* `is_current_url()` - returns true is the provided route name and parameters are valid for the current path.
* `current_url()` - returns the current path, with or without the query string.
* `get_uri()` - returns the `UriInterface` object from the incoming `ServerRequestInterface` object

You can use `url_for` to generate complete URLs to any Slim application named route and use `is_current_url` to determine if you need to mark a link as active as shown in this example Twig template:

```php
{% extends "layout.html" %}

{% block body %}
<h1>User List</h1>
<ul>
    <li><a href="{{ url_for('profile', { 'name': 'josh' }) }}" {% if is_current_url('profile', { 'name': 'josh' }) %}class="active"{% endif %}>Josh</a></li>
    <li><a href="{{ url_for('profile', { 'name': 'andrew' }) }}">Andrew</a></li>
</ul>
{% endblock %}
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
