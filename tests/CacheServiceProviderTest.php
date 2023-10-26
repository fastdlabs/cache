<?php


use FastD\Cache\CachePool;
use FastD\Cache\ServiceProvider\CacheServiceProvider;
use FastD\Cache\ServiceProvider\ServerRequestCacheProvider;
use FastD\Config\Config;
use FastD\Container\Container;
use FastD\Http\ServerRequest;
use FastD\Routing\RouteCollection;
use FastD\Routing\RouteDispatcher;
use FastD\Runtime\Runtime;
use PHPUnit\Framework\TestCase;

class CacheServiceProviderTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $config = new Config();
        $config->merge([
            'cache' => load(__DIR__ . '/src/config/cache.php')
        ]);
        $this->container->add('config', $config);
        Runtime::$container = $this->container;
        Runtime::$application = new \FastD\Application(__DIR__ . '/');
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

    /*public function testRedisConnect()
    {
        $serviceProvider = new CacheServiceProvider();
        $this->container->register($serviceProvider);
        $cache = $this->container->get('cache');
        $redisCache = $cache->getCache('redis');
        $item = $redisCache->getItem('foo');
        $value = 'bar';
        if (!$item->isHit()) {
            $item->set($value);
            $redisCache->save($item);
        }
        $this->assertEquals($value, $redisCache->getItem('foo')->get());
    }*/

    public function testCacheHit()
    {
        $serviceProvider = new CacheServiceProvider();
        $this->container->register($serviceProvider);
        $cache = $this->container->get('cache');
        $fileCache = $cache->getCache('file');
//        $redisCache = $cache->getCache('redis');
        $value = 'bar';
        $this->assertEquals($value, $fileCache->getItem('foo')->get());
//        $this->assertEquals($value, $redisCache->getItem('foo')->get());
//        $this->assertEquals($value, cache('redis')->getItem('foo')->get());
    }

    public function testServerRequestProvider()
    {
        $dispatcher = new RouteDispatcher(new RouteCollection());
        $this->container->add('dispatcher', $dispatcher);
        $dispatcher->getRouteCollection()->get('/', 'CacheServiceProviderTest@sayHello');
        $serviceProvider = new CacheServiceProvider();
        $serverRequestProvider = new ServerRequestCacheProvider();
        $this->container->register($serviceProvider);
        $this->container->register($serverRequestProvider);
        $response = $dispatcher->dispatch(new ServerRequest('GET', '/?channel=baidu'));
        $this->assertEquals('hello', (string)$response->getBody());
        $this->assertNotEmpty($response->getHeaderLine('X-Cache'));
        $response = $dispatcher->dispatch(new ServerRequest('GET', '/?tag=abc‘'));
        $this->assertNotEmpty($response->getHeaderLine('X-Cache'));
        $response = $dispatcher->dispatch(new ServerRequest('GET', '/'));
        $this->assertNotEmpty($response->getHeaderLine('X-Cache'));
        $response = $dispatcher->dispatch(new ServerRequest('GET', '/?channel=iqiyi'));
        $this->assertNotEmpty($response->getHeaderLine('X-Cache'));
    }

    public function sayHello()
    {
        return new \FastD\Http\Response('hello');
    }
}
