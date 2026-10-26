<?php

declare(strict_types=1);

namespace FastD\Cache\Tests\Middleware;

use FastD\Cache\Middleware\XMCache;
use FastD\Http\Request\ServerRequest;
use FastD\Http\Response\SseResponse;
use FastD\Http\Response\Text;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * 流式响应（SSE）不得进入缓存。
 */
class XMCacheSseBypassTest extends TestCase
{
    public function testStreamingResponseIsNotCached(): void
    {
        $middleware = new XMCache();
        $request = new ServerRequest('GET', '/sse');
        $response = new SseResponse(['data']);

        $method = new ReflectionMethod(XMCache::class, 'cacheResponse');
        $method->setAccessible(true);

        $dir = sys_get_temp_dir() . '/fastd-xmcache-sse-' . bin2hex(random_bytes(4));
        $adapter = new FilesystemAdapter('xmtest', 60, $dir);

        $result = $method->invoke($middleware, $request, $response, $adapter, 'cache-key', []);

        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);

        $this->assertSame($response, $result);
    }

    public function testRegularResponseStillCachesThroughTheSamePath(): void
    {
        $middleware = new XMCache();
        $request = new ServerRequest('GET', '/plain');
        $response = new Text(200, 'hello');

        $method = new ReflectionMethod(XMCache::class, 'cacheResponse');
        $method->setAccessible(true);

        $dir = sys_get_temp_dir() . '/fastd-xmcache-sse-' . bin2hex(random_bytes(4));
        $adapter = new FilesystemAdapter('xmtest', 60, $dir);
        $result = $method->invoke($middleware, $request, $response, $adapter, 'cache-key', []);

        $this->assertNotSame($response, $result);
        $this->assertTrue($adapter->getItem('cache-key')->isHit());

        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
