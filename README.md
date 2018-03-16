# Popeye

[![Build Status](https://travis-ci.org/HealthEngineAU/popeye.svg?branch=master)](https://travis-ci.org/HealthEngineAU/popeye)

Popeye is a generic middleware stack - because HTTP isn't the only thing that deserves middleware.

## Usage

This is a tiny library and is very easy to use. It's similar to
[Slim](https://www.slimframework.com/docs/v3/concepts/middleware.html)'s middleware implementation except it isn't
specific to HTTP - it can be used in any context. The following is an example that adds some type safety.

```php
<?php

use Popeye\HasMiddleware;
use Popeye\Lockable;

class SpecificMiddleware
{
    use HasMiddleware;

    public function __invoke(CertainType $input)
    {
        return $this->runStack($input);
    }

    public function add(CertainHandler $handler)
    {
        return $this->addHandler([$handler, 'handle']);
    }
}

interface CertainType
{
    public function getFoo();
    public function getBar();
}

interface CertainHandler
{
    public function handle(CertainType $input);
}
```

The code above ensures that only middleware of a certain implementing type can be added, as well as only a certain value
accepted to be passed through them, thus ensuring as much type safety as PHP can afford. It also implements the magic
`__invoke()` method so an instance of the middleware can be treated as a callable directly, which may or may not suit
your application.  
However, this example is a little convoluted and is not very efficient, since every handler object must be instantiated
even if the stack doesn't end up calling it.

Consider tying this into a framework that provides dependency injection, such as Laravel. As such, you are able to
leverage its ability to autowire dependencies on either a callable, or construct and call an object for you.  
This can be achieved similar to below. Of course, any other DI could be used - this library is not opinionated in that
facet.


```php
<?php

use Popeye\HasMiddleware;
use Popeye\Lockable;

class ContainerAwareMiddleware
{
    // use Lockable; // add this if you want to restrict middleware modifications once its being resolved.
    use HasMiddleware;

    public function add(string $handler)
    {
        return $this->addHandler($handler);
    }

    public function resolve()
    {
        return $this->runStack($input);
    }

    /**
     * Override the trait method to use the Laravel DI container to call the handler.
     */
    protected function callHandler($handler)
    {
        // have laravel construct and call the handler
        app()->call($handler);
    }
}
```

Of course, with this method we lose the type safety so you have to be careful to add handlers with a footprint
combatible with how the middleware will eventually be resolved, although Laravel will take a pretty good guess at trying
to resolve unspecified things. Guess we can't have everything ...

## License

Popeye is licensed under the MIT license.
