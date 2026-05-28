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
        // 从容器获取配置
        if ($container->has('config')) {
            $config = $container->get('config');
            $cacheConfig = is_array($config) ? ($config['cache'] ?? []) : [];
        } else {
            $cacheConfig = [];
        }

        $cachePool = new CachePool($cacheConfig);
        $container->add('cache', $cachePool);
        
        // 如果事件组件可用，注册监听器
        if ($container->has('event')) {
            $container->get('event')->addListener(new BootedEventListener());
        }
    }
}
