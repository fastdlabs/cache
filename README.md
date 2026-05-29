# FastD Cache

高性能 PHP 缓存库，基于 Symfony Cache 组件，专为 FastD 框架设计。

[![Latest Stable Version](https://poser.pugx.org/fastd/cache/v/stable)](https://packagist.org/packages/fastd/cache)
[![Total Downloads](https://poser.pugx.org/fastd/cache/downloads)](https://packagist.org/packages/fastd/cache)
[![License](https://poser.pugx.org/fastd/cache/license)](https://packagist.org/packages/fastd/cache)


[文档指南](docs/) | [问题反馈](https://github.com/fastdlabs/cache/issues)

## 特性

- 多层缓存架构（缓存池 + HTTP 中间件）
- 高性能设计（连接池、适配器复用）
- 灵活配置（支持文件、Redis、Memcached）
- 开箱即用（简洁 API，丰富配置）

## 文档

- [项目概述](docs/overview.md) - 架构设计和核心概念
- [安装指南](docs/installation/) - 环境配置和使用说明  
- [API 参考](docs/api/) - 类方法和配置选项
- [使用案例](docs/installation/#实际应用案例) - 实战示例

## 快速开始

### 环境依赖

- PHP >= 8.2
- Composer
- Symfony Cache ^8.0

### 安装配置

```bash
composer require fastd/cache
```

创建配置文件 `config/cache.php`：

```php
<?php
return [
    'file' => [
        'adapter' => [
            'class' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
        ],
        'namespace' => 'app',
        'lifetime' => 3600,
        'directory' => __DIR__ . '/../runtime/cache/',
    ]
];
```

注册服务提供者 `app.php`：

```php
<?php
return [
    'services' => [
        \FastD\Cache\ServiceProvider\CacheServiceProvider::class,
    ]
];
```

### 基础使用

```php
// 获取缓存适配器
$cache = cache('file');

// 存储数据
$item = $cache->getItem('user_123');
$item->set(['name' => 'John'])->expiresAfter(3600);
$cache->save($item);

// 读取数据
$item = $cache->getItem('user_123');
if ($item->isHit()) {
    $user = $item->get();
}
```

详细使用说明请查看[安装指南](docs/installation/)

## 支持的适配器

- File System - 开发环境，简单可靠
- Redis - 高并发场景，性能极高  
- Memcached - 键值缓存，配置简单
- PHP Files - 高频读取，性能最优

各适配器详细配置请查看[文档](docs/installation/#缓存适配器配置)

## 工作原理

### 缓存池架构

GET 请求 → 缓存检查 → 应用逻辑

- GET 请求缓存，其他请求直通
- CRC32 算法生成高效缓存键
- 内置连接池管理连接复用

### XMCache HTTP 中间件

XMCache 是一个强大的 HTTP 缓存中间件，提供页面级缓存能力：

**核心特性**：
- 自动缓存 GET 请求的 200 响应
- 智能缓存键生成（路径 + 查询参数）
- 缓存状态头标识（HIT/MISS）
- 可配置缓存生命周期
- 支持自定义缓存键

**使用示例**：

```php
// 配置缓存中间件
// config/cache.php
return [
    'httpCache' => [
        'lifetime' => 3600,  // 缓存 1 小时
        'cache_keys' => ['page', 'limit'],  // 参与缓存键的查询参数
    ],
];

// 注册中间件
// config/middleware.php
return [
    \FastD\Cache\Middleware\XMCache::class,
];
```

**响应头说明**：

```http
# 缓存未命中（首次请求）
X-M-Cache: mc1234567890
X-M-Cache-Status: MISS
Expires: Thu, 28 May 2026 17:00:00 GMT

# 缓存命中（后续请求）
X-M-Cache: mc1234567890
X-M-Cache-Status: HIT
Expires: Thu, 28 May 2026 17:00:00 GMT
```

**工作流程**：

1. 检查请求方法（仅缓存 GET）
2. 检查请求头中的自定义缓存键
3. 自动生成缓存键（路径 + 参数 CRC32）
4. 查询缓存，命中则直接返回
5. 未命中则执行请求，缓存响应
6. 设置缓存状态头和过期时间

## 测试

```bash
composer install
./vendor/bin/phpunit
```

## 贡献

欢迎提交 Issues 和 Pull Requests。

```bash
git clone https://github.com/fastdlabs/cache.git
cd cache
composer install
composer test
```

## 学习资源

- [官方文档](docs/) - 完整使用指南
- [问题反馈](https://github.com/fastdlabs/cache/issues) - Bug 报告
- [讨论区](https://github.com/fastdlabs/cache/discussions) - 技术交流

## 许可证

MIT License
