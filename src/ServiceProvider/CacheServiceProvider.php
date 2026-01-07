<?php

declare(strict_types=1);

namespace FastD\Cache\ServiceProvider;

use Exception;
use FastD\Cache\CachePool;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $cachePool = new CachePool($container->need('cache'));
        $container->add('cache', $cachePool);
        $container->add('onWorkerStart', [$cachePool]);
    }
}
