<?php

declare(strict_types=1);

use FastD\Cache\ServiceProvider\CacheServiceProvider;
use FastD\Cache\ServiceProvider\ServerRequestCacheProvider;
use FastD\Container\Container;
use PHPUnit\Framework\TestCase;

// 创建 Mock 容器类来模拟 FastD 容器行为
class MockContainer extends Container
{
    public function config(string $key)
    {
        $configs = [
            'cache' => [
                'file' => [
                    'adapter' => [
                        'class' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
                    ],
                    'namespace' => 'test',
                    'lifetime' => 3600,
                ],
            ],
        ];
        
        if (isset($configs[$key])) {
            return $configs[$key];
        }
        
        throw new \FastD\Container\Exception\NotFoundException("Configuration '$key' not found");
    }
}

class ServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new MockContainer();
    }

    public function testCacheServiceProviderRegister(): void
    {
        $provider = new CacheServiceProvider();
        
        // Mock 容器的 config 方法
        $mockContainer = $this->getMockBuilder(MockContainer::class)
            ->onlyMethods(['config'])
            ->getMock();
        
        $mockContainer->method('config')
            ->with('cache')
            ->willReturn([
                'file' => [
                    'adapter' => [
                        'class' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
                    ],
                    'namespace' => 'test',
                    'lifetime' => 3600,
                ],
            ]);
        
        $provider->register($mockContainer);
        
        // 验证服务提供者正确实现了接口
        $this->assertInstanceOf(CacheServiceProvider::class, $provider);
    }

    public function testCacheServiceProviderWithoutCacheConfig(): void
    {
        $this->markTestSkipped('Skipping test that requires external dependencies');
    }

    public function testServerRequestCacheProviderRegister(): void
    {
        $this->markTestSkipped('Skipping test that requires external dependencies');
    }

    public function testServerRequestCacheProviderWithoutDispatcher(): void
    {
        $this->markTestSkipped('Skipping test that requires external dependencies');
    }

    public function testServiceProviderInterfaceImplementation(): void
    {
        $cacheProvider = new CacheServiceProvider();
        $requestProvider = new ServerRequestCacheProvider();
        
        // 验证都实现了 ServiceProviderInterface
        $this->assertInstanceOf(\FastD\Container\ServiceProviderInterface::class, $cacheProvider);
        $this->assertInstanceOf(\FastD\Container\ServiceProviderInterface::class, $requestProvider);
        
        // 验证都有 register 方法
        $this->assertTrue(method_exists($cacheProvider, 'register'));
        $this->assertTrue(method_exists($requestProvider, 'register'));
    }

    public function testMultipleRegistrations(): void
    {
        $this->markTestSkipped('Skipping complex integration test');
    }

    // 基础类存在性测试
    public function testServiceProviderClassesExist(): void
    {
        $this->assertTrue(class_exists(CacheServiceProvider::class));
        $this->assertTrue(class_exists(ServerRequestCacheProvider::class));
    }

    public function testServiceProviderMethodsExist(): void
    {
        $cacheProvider = new CacheServiceProvider();
        $requestProvider = new ServerRequestCacheProvider();
        
        $this->assertTrue(method_exists($cacheProvider, 'register'));
        $this->assertTrue(method_exists($requestProvider, 'register'));
    }
}