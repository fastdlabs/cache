<?php

namespace tests;

use FastD\CacheProvider\CachePool;
use FastD\CacheProvider\ServiceProvider\CacheServiceProvider;
use FastD\Config\Config;
use FastD\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\CacheItem;

class CacheServiceProviderTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $config = new Config();
        $config->merge([
            'cache' => load(__DIR__ . '/../cache.php')
        ]);
        $this->container->add('config', $config);
    }

    public function testProviderInContainer()
    {
        $serviceProvider = new CacheServiceProvider();
        $this->container->register($serviceProvider);
        $this->assertInstanceOf(CachePool::class, $this->container->get('cache'));
    }

    public function testCacheConnect()
    {
        $serviceProvider = new CacheServiceProvider();
        $this->container->register($serviceProvider);
        $cache = $this->container->get('cache');
        $fileCache = $cache->getCache('file');
        $item = $fileCache->getItem('foo');
        $value = 'bar';
        if (!$item->isHit()) {
            $item->set($value);
            $fileCache->save($item);
        }
        $this->assertEquals($value, $fileCache->getItem('foo')->get());
    }

    public function testRedisConnect()
    {
        $serviceProvider = new CacheServiceProvider();
        $this->container->register($serviceProvider);
        $cache = $this->container->get('cache');
        $fileCache = $cache->getCache('redis');
    }
}
