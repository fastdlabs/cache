<?php

declare(strict_types=1);

namespace FastD\Cache\ServiceProvider;

use FastD\Cache\Middleware\XMCache;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;
use RuntimeException;

class ServerRequestCacheProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        if (!$container->has('config')) {
            throw new RuntimeException('The FastD config service is not registered.');
        }

        $config = $container->config('cache');

        if (!is_array($config)) {
            throw new RuntimeException('The cache configuration is missing.');
        }

        $httpCache = $config['httpCache'] ?? $config['xmCache'] ?? [];
        $httpCache = is_array($httpCache) ? $httpCache : [];

        if (!empty($httpCache['enable'])) {
            $container->get('matcher')->push(new XMCache());
        }
    }
}
