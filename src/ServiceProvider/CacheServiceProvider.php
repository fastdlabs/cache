<?php

declare(strict_types=1);

namespace FastD\Cache\ServiceProvider;

use ErrorException;
use Exception;
use FastD\Cache\CachePool;
use FastD\Cache\Listener\BootedEventListener;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        try {
            $config = $container->config('cache');
        } catch (ErrorException $e) {
            $file = container()->getRootPath() . '/config/cache.php';
            $config = config()->parse($file)->get('cache');
        }

        $cachePool = new CachePool($config);
        $container->add('cache', $cachePool);
        $container->got('event')->addListener(new BootedEventListener());
    }
}
