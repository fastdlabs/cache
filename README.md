# FastD Cache

[![Latest Stable Version](https://poser.pugx.org/fastd/cache/v/stable)](https://packagist.org/packages/fastd/cache)
[![Total Downloads](https://poser.pugx.org/fastd/cache/downloads)](https://packagist.org/packages/fastd/cache)
[![License](https://poser.pugx.org/fastd/cache/license)](https://packagist.org/packages/fastd/cache)

FastD Cache 是一个高性能的 PHP 缓存库，专为 [FastD PHP 框架](https://github.com/fastdlabs/fastd) 设计。它参考了 Varnish 架构中的缓存机制，提供了从底层缓存到 HTTP 层面的完整缓存解决方案。

## 🚀 主要特性

- **高性能缓存** - 基于 Symfony Cache 组件，支持多种缓存适配器
- **灵活配置** - 支持文件系统、Redis、Memcached 等多种缓存后端
- **连接池管理** - 自动管理缓存连接，提高性能
- **HTTP 中间件** - 内置 HTTP 缓存中间件，支持页面级缓存
- **服务提供者** - 完整的服务容器集成
- **辅助函数** - 简洁的 `cache()` 辅助函数调用

## 📖 文档

完整的文档请查看 [docs/](docs/) 目录：

- [项目概述](docs/overview.md) - 详细介绍项目架构、设计理念和技术特性
- [API 参考](docs/api/) - 核心类和方法的详细说明及使用示例
- [安装与使用](docs/installation/) - 安装指南、配置说明和实际应用案例

## 🛠️ 快速开始

### 安装

```bash
composer require fastd/cache
```

### 基本配置

1. 在 `config/cache.php` 中添加配置：

```php
<?php

return [
    'file' => [
        'adapter' => [
            'class' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
        ],
        'namespace' => 'myapp',
        'lifetime' => 3600,
        'directory' => __DIR__ . '/../runtime/cache/',
    ],
];
```

2. 在 `app.php` 中注册服务提供者：

```php
<?php 

return [
    'services' => [
        \FastD\Cache\ServiceProvider\CacheServiceProvider::class,
        // 可选：启用 HTTP 缓存中间件
        \FastD\Cache\ServiceProvider\ServerRequestCacheProvider::class,
    ]
];
```

### 基本使用

```php
// 使用辅助函数快速访问缓存
$cache = cache('file');

// 存储数据
$item = $cache->getItem('user_123');
$item->set(['name' => 'John'])->expiresAfter(3600);
$cache->save($item);

// 读取数据
$item = $cache->getItem('user_123');
if ($item->isHit()) {
    $user = $item->get();
    echo $user['name'];
}
```

## 🔧 支持的缓存适配器

- **文件系统缓存** - `FilesystemAdapter`
- **Redis 缓存** - `RedisAdapter`
- **Memcached 缓存** - `MemcachedAdapter`
- **PHP 文件缓存** - `PhpFilesAdapter`

## 🏗️ 架构原理

```
用户请求 → HTTP 中间件 → 缓存检查 → 应用逻辑
    ↑                                    ↓
    ←←←←←←← 缓存命中直接返回 ←←←←←←←← 缓存存储
```

工作流程：
1. GET 请求到达时，中间件首先检查是否有对应缓存
2. 缓存命中则直接返回缓存内容
3. 缓存未命中则执行应用逻辑，并将结果缓存
4. 非 GET 请求直接穿透，不进行缓存

## 📋 系统要求

- PHP >= 8.2
- Composer
- Symfony Cache 组件

## 🧪 运行测试

```bash
composer install
./vendor/bin/phpunit
```

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📞 支持

如果你在使用中遇到问题，请联系：

- Email: [bboyjanhuang@gmail.com](mailto:bboyjanhuang@gmail.com)
- 微博: [编码侠](http://weibo.com/ecbboyjan)
- GitHub Issues: [提交问题](https://github.com/fastdlabs/cache/issues)

## 📄 许可证

MIT License

Copyright (c) 2024 JanHuang

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
