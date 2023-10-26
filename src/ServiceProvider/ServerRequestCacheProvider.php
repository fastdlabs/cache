<?php

namespace FastD\Cache\ServiceProvider;

use FastD\Cache\Middleware\ServerRequestCache;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;

class ServerRequestCacheProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->get('dispatcher')->push(new ServerRequestCache());
    }
}
