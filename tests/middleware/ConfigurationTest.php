<?php

declare(strict_types=1);

namespace middleware;

use FastD\Cache\CachePool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class ConfigurationTest extends TestCase
{
    public function testXMCacheConfigurationStructure(): void
    {
        $config = [
            'xmCache' => [
                'enable' => false,
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                ],
                'lifetime' => 60,
                'directory' => '/tmp/test_cache/',
                'cache_keys' => ['foo'],
            ]
        ];

        $cachePool = new CachePool($config);
        $this->assertInstanceOf(CachePool::class, $cachePool);

        // 验证配置被正确存储
        $reflection = new \ReflectionClass($cachePool);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $storedConfig = $configProperty->getValue($cachePool);

        $this->assertArrayHasKey('xmCache', $storedConfig);
        $this->assertEquals($config['xmCache'], $storedConfig['xmCache']);
    }

    public function testMultipleCacheTypesConfiguration(): void
    {
        $config = [
            'file' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                ],
                'namespace' => 'file_cache',
                'lifetime' => 3600,
            ],
            'xmCache' => [
                'enable' => true,
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                ],
                'lifetime' => 120,
                'cache_keys' => ['id', 'type'],
            ],
            'redis' => [
                'adapter' => [
                    'class' => \Symfony\Component\Cache\Adapter\RedisAdapter::class,
                    'dsn' => 'redis://localhost:6379/0',
                ],
                'namespace' => 'redis_cache',
            ]
        ];

        $cachePool = new CachePool($config);

        // 测试可以获取不同类型的缓存适配器
        $fileAdapter = $cachePool->getAdapter('file');
        $this->assertInstanceOf(FilesystemAdapter::class, $fileAdapter);

        // 注意：xmCache 配置主要用于中间件，不在 CachePool 中直接使用
        // 但配置应该能被正确解析
    }

    public function testCacheKeysConfiguration(): void
    {
        $configs = [
            // 测试不同的 cache_keys 配置
            ['cache_keys' => []],
            ['cache_keys' => ['id']],
            ['cache_keys' => ['id', 'type', 'sort']],
            ['cache_keys' => null],
            [], // 没有 cache_keys
        ];

        foreach ($configs as $config) {
            $fullConfig = [
                'xmCache' => array_merge([
                    'adapter' => [
                        'class' => FilesystemAdapter::class,
                    ],
                    'lifetime' => 60,
                ], $config)
            ];

            $cachePool = new CachePool($fullConfig);
            // 配置应该能被正确解析而不抛出异常
            $this->assertInstanceOf(CachePool::class, $cachePool);
        }
    }

    public function testEnableFlagHandling(): void
    {
        // 测试 enable 标志的不同值
        $enableValues = [true, false, null, 1, 0, 'true', 'false'];

        foreach ($enableValues as $enableValue) {
            $config = [
                'xmCache' => [
                    'enable' => $enableValue,
                    'adapter' => [
                        'class' => FilesystemAdapter::class,
                    ],
                ]
            ];

            $cachePool = new CachePool($config);
            $this->assertInstanceOf(CachePool::class, $cachePool);

            // 验证配置被正确存储
            $reflection = new \ReflectionClass($cachePool);
            $configProperty = $reflection->getProperty('config');
            $configProperty->setAccessible(true);
            $storedConfig = $configProperty->getValue($cachePool);

            $this->assertEquals($enableValue, $storedConfig['xmCache']['enable']);
        }
    }

    public function testDirectoryConfiguration(): void
    {
        $directories = [
            '/tmp/cache/',
            __DIR__ . '/runtime/cache/',
            sys_get_temp_dir() . '/test_cache/',
            '', // 空字符串
            null, // null 值
        ];

        foreach ($directories as $directory) {
            $config = [
                'xmCache' => [
                    'adapter' => [
                        'class' => FilesystemAdapter::class,
                    ],
                    'directory' => $directory,
                ]
            ];

            $cachePool = new CachePool($config);
            $this->assertInstanceOf(CachePool::class, $cachePool);
        }
    }

    public function testLifetimeConfiguration(): void
    {
        $lifetimes = [
            60,      // 1分钟
            3600,    // 1小时
            86400,   // 1天
            0,       // 永不过期
            null,    // 默认值
            -1,      // 负数（应该被处理）
        ];

        foreach ($lifetimes as $lifetime) {
            $config = [
                'xmCache' => [
                    'adapter' => [
                        'class' => FilesystemAdapter::class,
                    ],
                    'lifetime' => $lifetime,
                ]
            ];

            $cachePool = new CachePool($config);
            $this->assertInstanceOf(CachePool::class, $cachePool);
        }
    }
}