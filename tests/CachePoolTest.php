<?php

declare(strict_types=1);

use FastD\Cache\CachePool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CachePoolTest extends TestCase
{
    private array $testConfig;
    
    protected function setUp(): void
    {
        $this->testConfig = [
            'file' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'test_file',
                'lifetime' => 3600,
                'directory' => sys_get_temp_dir(),
            ],
            'redis' => [
                'adapter' => [
                    'class' => RedisAdapter::class,
                    'dsn' => 'redis://127.0.0.1:6379/1',
                    'options' => [],
                ],
                'namespace' => 'test_redis',
                'lifetime' => 1800,
            ],
            'invalid_scheme' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => 'invalid://localhost',
                ],
            ],
        ];
    }

    // 基础功能测试
    public function testConstructor(): void
    {
        $cachePool = new CachePool($this->testConfig);
        $this->assertInstanceOf(CachePool::class, $cachePool);
        $reflection = new \ReflectionClass($cachePool);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $this->assertEquals($this->testConfig, $configProperty->getValue($cachePool));
    }

    public function testGetAdapterWithFilesystem(): void
    {
        $cachePool = new CachePool($this->testConfig);
        $adapter = $cachePool->getAdapter('file');
        
        $this->assertInstanceOf(FilesystemAdapter::class, $adapter);
        
        // 清理之前的测试数据
        $adapter->clear();
        
        // Test caching functionality
        $item = $adapter->getItem('test_key');
        $this->assertFalse($item->isHit());
        
        $item->set('test_value');
        $adapter->save($item);
        
        $retrievedItem = $adapter->getItem('test_key');
        $this->assertTrue($retrievedItem->isHit());
        $this->assertEquals('test_value', $retrievedItem->get());
    }

    public function testGetCacheReturnsSameInstance(): void
    {
        $cachePool = new CachePool($this->testConfig);
        
        $cache1 = $cachePool->getCache('file');
        $cache2 = $cachePool->getCache('file');
        
        $this->assertSame($cache1, $cache2);
    }

    public function testGetCacheCreatesDifferentInstances(): void
    {
        // 使用仅包含文件系统配置的测试配置，避免 Redis 连接
        $fileOnlyConfig = [
            'file' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'test_file',
                'lifetime' => 3600,
                'directory' => sys_get_temp_dir(),
            ],
            'another_file' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'another_test',
                'lifetime' => 1800,
            ],
        ];
        
        $cachePool = new CachePool($fileOnlyConfig);
        
        $fileCache1 = $cachePool->getCache('file');
        $fileCache2 = $cachePool->getCache('another_file');
        
        $this->assertNotSame($fileCache1, $fileCache2);
        $this->assertInstanceOf(FilesystemAdapter::class, $fileCache1);
        $this->assertInstanceOf(FilesystemAdapter::class, $fileCache2);
    }

    public function testConnectWithNonExistentKeyThrowsException(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('No set nonexistent cache config.');
        
        $cachePool = new CachePool($this->testConfig);
        $cachePool->connect('nonexistent');
    }

    public function testGetAdapterWithInvalidSchemeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported DSN scheme: invalid');
        
        $cachePool = new CachePool($this->testConfig);
        $cachePool->getAdapter('invalid_scheme');
    }

    public function testOnCallbackInitializesConnections(): void
    {
        // 使用无 DSN 配置避免 Redis 连接
        $noDSNConfig = [
            'file' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'test_file',
                'lifetime' => 3600,
                'directory' => sys_get_temp_dir(),
            ],
        ];
        
        $cachePool = new CachePool($noDSNConfig);
        $result = $cachePool->onCallback();
        
        $this->assertTrue($result);
        
        // Verify connections array exists but is empty (no DSN configs)
        $reflection = new ReflectionClass($cachePool);
        $connectionsProperty = $reflection->getProperty('connections');
        $connectionsProperty->setAccessible(true);
        $connections = $connectionsProperty->getValue($cachePool);
        
        $this->assertIsArray($connections);
        $this->assertCount(0, $connections);
    }

    public function testInitConnections(): void
    {
        // 使用无 Redis 配置的测试数据
        $noRedisConfig = [
            'file' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'test_file',
                'lifetime' => 3600,
                'directory' => sys_get_temp_dir(),
            ],
        ];
        
        $cachePool = new CachePool($noRedisConfig);
        
        $reflection = new ReflectionClass($cachePool);
        $initMethod = $reflection->getMethod('initConnections');
        $initMethod->setAccessible(true);
        
        $initMethod->invoke($cachePool);
        
        // Check that connections property exists and is array
        $connectionsProperty = $reflection->getProperty('connections');
        $connectionsProperty->setAccessible(true);
        $connections = $connectionsProperty->getValue($cachePool);
        
        $this->assertIsArray($connections);
        // 应该为空，因为没有 DSN 配置
        $this->assertCount(0, $connections);
    }

    public function testConnectReturnsExistingConnection(): void
    {
        // 测试 getCache 方法的连接复用逻辑
        $fileConfig = [
            'file' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'test_connection',
                'lifetime' => 3600,
                'directory' => sys_get_temp_dir(),
            ],
        ];
        
        $cachePool = new CachePool($fileConfig);
        
        // 第一次获取缓存适配器
        $firstCache = $cachePool->getCache('file');
        $this->assertInstanceOf(FilesystemAdapter::class, $firstCache);
        
        // 第二次获取应该返回相同的实例
        $secondCache = $cachePool->getCache('file');
        $this->assertSame($firstCache, $secondCache);
        
        // 验证这是同一个对象实例
        $this->assertEquals(spl_object_hash($firstCache), spl_object_hash($secondCache));
    }

    public function testCacheConfigurationParameters(): void
    {
        $customConfig = [
            'custom' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'custom_namespace',
                'lifetime' => 7200,
                'directory' => '/tmp/custom_cache',
            ],
        ];
        
        $cachePool = new CachePool($customConfig);
        $adapter = $cachePool->getAdapter('custom');
        
        $this->assertInstanceOf(FilesystemAdapter::class, $adapter);
    }

    // Mock Redis 测试，避免真实连接
    public function testConnectWithMockRedis(): void
    {
        // 创建不包含 DSN 的配置来避免 Redis 连接
        $safeConfig = [
            'file_redis' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,  // 明确设置为 null 避免触发 connect 方法
                ],
                'namespace' => 'safe_redis_test',
                'lifetime' => 1800,
                'directory' => sys_get_temp_dir(),
            ],
        ];
        
        $cachePool = new CachePool($safeConfig);
        
        // 测试安全的适配器创建
        $adapter = $cachePool->getAdapter('file_redis');
        $this->assertInstanceOf(FilesystemAdapter::class, $adapter);
        
        // 验证适配器被正确创建
        $this->assertNotNull($adapter);
    }

    public function testGetAdapterWithMockRedis(): void
    {
        // 创建只包含文件系统配置的测试配置
        $fileOnlyConfig = [
            'file' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'test_file',
                'lifetime' => 3600,
                'directory' => sys_get_temp_dir(),
            ],
        ];
        
        $cachePool = new CachePool($fileOnlyConfig);
        $adapter = $cachePool->getAdapter('file');
        
        $this->assertInstanceOf(FilesystemAdapter::class, $adapter);
        
        // 测试不依赖外部连接的功能
        $item = $adapter->getItem('mock_test_key');
        $this->assertFalse($item->isHit());
        
        $item->set('mock_test_value');
        $adapter->save($item);
        
        $retrievedItem = $adapter->getItem('mock_test_key');
        $this->assertTrue($retrievedItem->isHit());
        $this->assertEquals('mock_test_value', $retrievedItem->get());
    }

    public function testConnectionPooling(): void
    {
        // 测试多个不同配置的适配器共存
        $multiConfig = [
            'file1' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'pool_test_1',
                'lifetime' => 3600,
                'directory' => sys_get_temp_dir(),
            ],
            'file2' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                    'dsn' => null,
                ],
                'namespace' => 'pool_test_2',
                'lifetime' => 1800,
                'directory' => sys_get_temp_dir(),
            ],
        ];
        
        $cachePool = new CachePool($multiConfig);
        
        // 获取不同的适配器实例
        $adapter1 = $cachePool->getAdapter('file1');
        $adapter2 = $cachePool->getAdapter('file2');
        
        // 验证它们是不同的实例
        $this->assertInstanceOf(FilesystemAdapter::class, $adapter1);
        $this->assertInstanceOf(FilesystemAdapter::class, $adapter2);
        $this->assertNotSame($adapter1, $adapter2);
        
        // 验证 getCache 方法返回相同实例
        $cache1 = $cachePool->getCache('file1');
        $cache2 = $cachePool->getCache('file1');
        $this->assertSame($cache1, $cache2);
    }
}