<?php

declare(strict_types=1);

namespace FastD\Cache\ServiceProvider;

use ErrorException;
use FastD\Cache\Middleware\XMCache;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;

class ServerRequestCacheProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        try {
            $config = $container->config('cache');
        } catch (ErrorException $e) {
            $file = container()->getRootPath() . '/config/cache.php';
            $config = config()->parse($file)->get('cache');
        }
        if (isset($config['xmCache']['enable']) && $config['xmCache']['enable']) {
            $container->get('matcher')->push(new XMCache());
        }
    }
}
