<?php

namespace FastD\CacheProvider\ServiceProvider;

use FastD\CacheProvider\Middleware\ServerRequestCache;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;

class ServerRequestCacheProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->get('dispatcher')->push(new ServerRequestCache());
    }
}
