<?php

declare(strict_types=1);

namespace FastD\Cache\Tests\Middleware;

use FastD\Cache\Middleware\XMCache;
use FastD\Http\Request\ServerRequest;
use FastD\Http\Response\Text;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class XMCachePrivacyTest extends TestCase
{
    public function testRequestWithCookieOrAuthorizationIsNotCacheable(): void
    {
        $middleware = new XMCache();
        $method = new ReflectionMethod(XMCache::class, 'shouldBypassRequest');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($middleware, (new ServerRequest('GET', '/'))->withHeader('Cookie', 'sid=abc')));
        $this->assertTrue($method->invoke($middleware, (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Bearer token')));
        $this->assertTrue($method->invoke($middleware, (new ServerRequest('GET', '/'))->withHeader('Cache-Control', 'no-store')));
        $this->assertFalse($method->invoke($middleware, new ServerRequest('GET', '/')));
    }

    public function testPrivateOrSetCookieResponseIsNotCacheable(): void
    {
        $middleware = new XMCache();
        $method = new ReflectionMethod(XMCache::class, 'shouldBypassResponse');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($middleware, new Text(200, 'plain')));
        $this->assertTrue($method->invoke($middleware, (new Text(200, 'cookie'))->withHeader('Set-Cookie', 'sid=abc')));
        $this->assertTrue($method->invoke($middleware, (new Text(200, 'private'))->withHeader('Cache-Control', 'private, max-age=60')));
        $this->assertTrue($method->invoke($middleware, (new Text(200, 'no-store'))->withHeader('Cache-Control', 'no-store')));
        $this->assertTrue($method->invoke($middleware, (new Text(200, 'vary'))->withHeader('Vary', 'Cookie')));
    }

    public function testSetCookieResponseIsNotWrittenToCache(): void
    {
        $middleware = new XMCache();
        $request = new ServerRequest('GET', '/private');
        $response = (new Text(200, 'private'))->withHeader('Set-Cookie', 'sid=abc');
        $method = new ReflectionMethod(XMCache::class, 'cacheResponse');
        $method->setAccessible(true);

        $dir = sys_get_temp_dir() . '/fastd-xmcache-privacy-' . bin2hex(random_bytes(4));
        $adapter = new FilesystemAdapter('xmtest', 60, $dir);

        $result = $method->invoke($middleware, $request, $response, $adapter, 'cache-key', []);

        $this->assertSame($response, $result);
        $this->assertFalse($adapter->getItem('cache-key')->isHit());

        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
