<?php


use FastD\Application;
use FastD\Cache\CachePool;
use FastD\Cache\ServiceProvider\CacheServiceProvider;
use FastD\Cache\ServiceProvider\ServerRequestCacheProvider;
use FastD\Config\Config;
use FastD\Container\Container;
use FastD\Http\ServerRequest;
use FastD\Routing\RouteCollection;
use FastD\Routing\RouteDispatcher;
use PHPUnit\Framework\TestCase;

class CacheServiceProviderTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Application([
            'env' => 'tests',
            'path' => __DIR__,
            'app' => [],
            'routes' => [],
            'services' => [],
            'cache' => __DIR__ . '/config/cache.php',
        ]);
        new \FastD\Server\FastCGI($this->container);
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

        $response1 = $dispatcher->dispatch(new ServerRequest('GET', '/?foo=bar'));
        $this->assertEquals('hello', (string)$response1->getBody());
        $this->assertNotEmpty($response1->getHeaderLine('X-Cache'));

        $response2 = $dispatcher->dispatch(new ServerRequest('GET', '/?bar=foo‘'));
        $this->assertNotEmpty($response2->getHeaderLine('X-Cache'));

        $response3 = $dispatcher->dispatch(new ServerRequest('GET', '/'));
        $this->assertNotEmpty($response3->getHeaderLine('X-Cache'));

        $response4 = $dispatcher->dispatch(new ServerRequest('GET', '/?foo=boll'));
        $this->assertNotEmpty($response4->getHeaderLine('X-Cache'));

        $this->assertTrue($response1->getHeaderLine('X-Cache') !== $response4->getHeaderLine('X-Cache'));
        $this->assertTrue($response1->getHeaderLine('X-Cache') !== $response2->getHeaderLine('X-Cache'));
        $this->assertTrue($response3->getHeaderLine('X-Cache') == $response2->getHeaderLine('X-Cache'));
        $this->assertEquals($response3->getHeaderLine('X-Cache'), $response2->getHeaderLine('X-Cache'));
        $this->assertEquals(120, $response4->getMaxAge());
    }

    public function sayHello()
    {
        return new \FastD\Http\Response('hello');
    }
}
