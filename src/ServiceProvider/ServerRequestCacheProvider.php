<?php

declare(strict_types=1);

namespace FastD\Cache\ServiceProvider;

use FastD\Cache\Middleware\HttpCache;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;

class ServerRequestCacheProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->got('dispatcher')->push(new HttpCache());
    }
}
