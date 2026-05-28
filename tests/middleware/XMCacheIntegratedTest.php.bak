<?php

declare(strict_types=1);

use FastD\Cache\Middleware\XMCache;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\CacheItem;

class XMCacheTest extends TestCase
{
    private XMCache $xmCache;
    private RequestHandlerInterface $mockHandler;

    protected function setUp(): void
    {
        $this->xmCache = new XMCache();
        $this->mockHandler = $this->createMock(RequestHandlerInterface::class);
    }

    // === 单元测试部分（不依赖外部服务）===

    public function testConstants(): void
    {
        $this->assertEquals('httpCache', XMCache::CacheName);
        $this->assertEquals('X-M-Cache', XMCache::HeaderKey);
        $this->assertEquals('X-M-Cache-Status', XMCache::HeaderStatusKey);
    }

    public function testGenerateCacheKeyWithPathOnly(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockUri = $this->createMock(UriInterface::class);
        
        $mockUri->method('getPath')->willReturn('/api/users');
        $mockRequest->method('getUri')->willReturn($mockUri);
        $mockRequest->method('getQueryParams')->willReturn([]);

        $reflection = new ReflectionClass($this->xmCache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $config = [];
        $version = 1;
        $cacheKey = $method->invoke($this->xmCache, $mockRequest, $config, $version);

        $expectedSource = '/api/users' . $version;
        $expectedKey = 'mh' . sprintf("%u", crc32($expectedSource));
        $this->assertEquals($expectedKey, $cacheKey);
    }

    public function testGenerateCacheKeyWithQueryParams(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockUri = $this->createMock(UriInterface::class);
        
        $mockUri->method('getPath')->willReturn('/api/users');
        $mockRequest->method('getUri')->willReturn($mockUri);
        $mockRequest->method('getQueryParams')->willReturn(['sort' => 'name', 'limit' => '10']);

        $reflection = new ReflectionClass($this->xmCache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $config = ['cache_keys' => ['sort', 'limit']];
        $version = 1;
        $cacheKey = $method->invoke($this->xmCache, $mockRequest, $config, $version);

        $expectedSource = '/api/users?' . http_build_query(['limit' => '10', 'sort' => 'name'], '', '&', PHP_QUERY_RFC3986) . $version;
        $expectedKey = 'mh' . sprintf("%u", crc32($expectedSource));
        $this->assertEquals($expectedKey, $cacheKey);
    }

    public function testGenerateCacheKeyFiltersParams(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockUri = $this->createMock(UriInterface::class);
        
        $mockUri->method('getPath')->willReturn('/products');
        $mockRequest->method('getUri')->willReturn($mockUri);
        $mockRequest->method('getQueryParams')->willReturn(['page' => '1', 'category' => 'electronics', 'sort' => 'price']);

        $reflection = new ReflectionClass($this->xmCache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $config = ['cache_keys' => ['category', 'sort']];
        $version = 2;
        $cacheKey = $method->invoke($this->xmCache, $mockRequest, $config, $version);

        $expectedSource = '/products?' . http_build_query(['category' => 'electronics', 'sort' => 'price'], '', '&', PHP_QUERY_RFC3986) . $version;
        $expectedKey = 'mh' . sprintf("%u", crc32($expectedSource));
        $this->assertEquals($expectedKey, $cacheKey);
    }

    public function testDifferentPathsGenerateDifferentKeys(): void
    {
        $mockRequest1 = $this->createMock(ServerRequestInterface::class);
        $mockUri1 = $this->createMock(UriInterface::class);
        $mockUri1->method('getPath')->willReturn('/api/users');
        $mockRequest1->method('getUri')->willReturn($mockUri1);
        $mockRequest1->method('getQueryParams')->willReturn([]);

        $mockRequest2 = $this->createMock(ServerRequestInterface::class);
        $mockUri2 = $this->createMock(UriInterface::class);
        $mockUri2->method('getPath')->willReturn('/api/products');
        $mockRequest2->method('getUri')->willReturn($mockUri2);
        $mockRequest2->method('getQueryParams')->willReturn([]);

        $reflection = new ReflectionClass($this->xmCache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $config = [];
        $version = 1;
        
        $key1 = $method->invoke($this->xmCache, $mockRequest1, $config, $version);
        $key2 = $method->invoke($this->xmCache, $mockRequest2, $config, $version);
        
        $this->assertNotEquals($key1, $key2);
    }

    public function testSamePathDifferentParamsGenerateDifferentKeys(): void
    {
        $mockRequest1 = $this->createMock(ServerRequestInterface::class);
        $mockUri1 = $this->createMock(UriInterface::class);
        $mockUri1->method('getPath')->willReturn('/api/users');
        $mockRequest1->method('getUri')->willReturn($mockUri1);
        $mockRequest1->method('getQueryParams')->willReturn(['sort' => 'name']);

        $mockRequest2 = $this->createMock(ServerRequestInterface::class);
        $mockUri2 = $this->createMock(UriInterface::class);
        $mockUri2->method('getPath')->willReturn('/api/users');
        $mockRequest2->method('getUri')->willReturn($mockUri2);
        $mockRequest2->method('getQueryParams')->willReturn(['sort' => 'date']);

        $reflection = new ReflectionClass($this->xmCache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $config = ['cache_keys' => ['sort']];
        $version = 1;
        
        $key1 = $method->invoke($this->xmCache, $mockRequest1, $config, $version);
        $key2 = $method->invoke($this->xmCache, $mockRequest2, $config, $version);
        
        $this->assertNotEquals($key1, $key2);
    }

    public function testVersionAffectsCacheKey(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')->willReturn('/api/data');
        $mockRequest->method('getUri')->willReturn($mockUri);
        $mockRequest->method('getQueryParams')->willReturn([]);

        $reflection = new ReflectionClass($this->xmCache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $config = [];
        
        $key1 = $method->invoke($this->xmCache, $mockRequest, $config, 1);
        $key2 = $method->invoke($this->xmCache, $mockRequest, $config, 2);
        
        $this->assertNotEquals($key1, $key2);
    }

    public function testEmptyCacheKeysConfig(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')->willReturn('/api/test');
        $mockRequest->method('getUri')->willReturn($mockUri);
        $mockRequest->method('getQueryParams')->willReturn(['ignored' => 'param']);

        $reflection = new ReflectionClass($this->xmCache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $config = [];
        $version = 1;
        $cacheKey = $method->invoke($this->xmCache, $mockRequest, $config, $version);

        $expectedSource = '/api/test' . $version;
        $expectedKey = 'mh' . sprintf("%u", crc32($expectedSource));
        $this->assertEquals($expectedKey, $cacheKey);
    }

    public function testNullCacheKeysConfig(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')->willReturn('/api/test');
        $mockRequest->method('getUri')->willReturn($mockUri);
        $mockRequest->method('getQueryParams')->willReturn(['param' => 'value']);

        $reflection = new ReflectionClass($this->xmCache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $config = ['cache_keys' => null];
        $version = 1;
        $cacheKey = $method->invoke($this->xmCache, $mockRequest, $config, $version);

        $expectedSource = '/api/test' . $version;
        $expectedKey = 'mh' . sprintf("%u", crc32($expectedSource));
        $this->assertEquals($expectedKey, $cacheKey);
    }

    // === Mock 缓存测试部分 ===

    public function testGetFromCacheReturnsNullWhenNotHit(): void
    {
        $this->markTestSkipped('Cannot mock final CacheItem class');
    }

    public function testGetFromCacheDeletesInvalidData(): void
    {
        $this->markTestSkipped('Cannot mock final CacheItem class');
    }

    public function testCacheResponseAddsHeaders(): void
    {
        $this->markTestSkipped('Cannot mock final CacheItem class');
    }

    // === 集成测试部分（需要适当 Mock）===

    public function testProcessNonGetMethodBypassesCache(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockRequest->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($mockRequest)
            ->willReturn($mockResponse);

        $result = $this->xmCache->process($mockRequest, $this->mockHandler);

        $this->assertSame($mockResponse, $result);
    }

    public function testProcessNonOkStatusBypassesCache(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockRequest->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($mockRequest)
            ->willReturn($mockResponse);

        $mockResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(404); // 非 200 状态码

        $result = $this->xmCache->process($mockRequest, $this->mockHandler);

        $this->assertSame($mockResponse, $result);
    }

    public function testCompleteCacheMissScenario(): void
    {
        $this->markTestSkipped('Cannot mock final CacheItem class');
    }
}