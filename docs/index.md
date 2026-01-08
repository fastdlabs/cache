# FastD Cache 文档

欢迎使用 FastD Cache！这是一个基于 Symfony Cache 组件构建的高性能缓存库，专为 FastD PHP 框架设计。

## 目录导航

- [项目概述](overview.md) - 项目简介、技术架构和设计理念
- [API 参考](api/) - 核心类和方法的详细说明
- [安装与使用](installation/) - 安装指南、配置说明和使用示例

## 快速开始

```bash
composer require fastd/cache
```

```php
// 注册服务提供者
return [
    'services' => [
        \FastD\Cache\ServiceProvider\CacheServiceProvider::class,
    ]
];
```

## 主要特性

- 🚀 **高性能缓存** - 基于 Symfony Cache 组件，支持多种缓存适配器
- 🔧 **灵活配置** - 支持文件系统、Redis、Memcached 等多种缓存后端
- 🔄 **连接池管理** - 自动管理缓存连接，提高性能
- 🛡️ **HTTP 中间件** - 内置 HTTP 缓存中间件，支持页面级缓存
- 📦 **服务提供者** - 完整的服务容器集成
- 💡 **辅助函数** - 简洁的 `cache()` 辅助函数调用

## 许可证

MIT License