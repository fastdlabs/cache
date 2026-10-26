<?php

declare(strict_types=1);

namespace FastD\Cache\ServiceProvider;

use FastD\Cache\CachePool;
use FastD\Cache\Listener\BootedEventListener;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;
use RuntimeException;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        if (!$container->has('config')) {
            throw new RuntimeException('The FastD config service is not registered.');
        }

        $config = $container->config('cache');

        if (!is_array($config) || $config === []) {
            throw new RuntimeException('The cache configuration is missing.');
        }

        $container->add('cache', new CachePool($config));

        if ($container->has('event')) {
            $container->get('event')->addListener(new BootedEventListener());
        }
    }
}
