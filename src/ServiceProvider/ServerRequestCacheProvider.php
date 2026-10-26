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
        $config = is_array($config) ? $config : [];
        $httpCache = $config['httpCache'] ?? $config['xmCache'] ?? [];
        $httpCache = is_array($httpCache) ? $httpCache : [];

        if (!empty($httpCache['enable'])) {
            $container->get('matcher')->push(new XMCache());
        }
    }
}
