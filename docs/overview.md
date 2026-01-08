# 项目概述

## 项目简介和核心功能

FastD Cache 是一个专门为 [FastD PHP 框架](https://github.com/fastdlabs/fastd) 设计的高性能缓存库。它参考了 Varnish 架构中的缓存机制，提供了从底层缓存到 HTTP 层面的完整缓存解决方案。

### 核心功能

#### 1. 多层缓存架构
- **底层缓存池 (CachePool)** - 提供统一的缓存适配器管理
- **HTTP 中间件缓存 (XMCache)** - 基于请求 URL 的页面级缓存
- **服务提供者集成** - 与 FastD 容器无缝集成

#### 2. 支持的缓存适配器
- **文件系统缓存** - 使用本地文件存储缓存数据
- **Redis 缓存** - 高性能分布式缓存
- **Memcached 缓存** - 内存级缓存解决方案
- **PHP 文件缓存** - 基于 OPcache 的高性能缓存

## 技术架构和依赖说明

### 核心架构

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   HTTP 请求     │───▶│   XMCache 中间件  │───▶│   应用逻辑      │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────┐
                       │  CachePool  │
                       └─────────────┘
                              │
                    ┌─────────┼─────────┐
                    ▼         ▼         ▼
              ┌──────────┐ ┌───────┐ ┌──────────┐
              │  Redis   │ │ File  │ │ Memcache │
              └──────────┘ └───────┘ └──────────┘
```

### 主要依赖

```json
{
    "require": {
        "PHP": ">=8.2",
        "symfony/cache": "^8.0",
        "predis/predis": "^3.0"
    }
}
```

### 核心组件关系

#### CachePool (缓存池)
- 管理所有缓存适配器的生命周期
- 提供连接池功能，避免重复连接
- 统一的缓存接口访问

#### XMCache (HTTP 缓存中间件)
- 实现 PSR-15 中间件标准
- 基于请求 URL 生成缓存键
- 支持自定义缓存键参数过滤
- 提供缓存命中状态标识

#### 服务提供者
- `CacheServiceProvider` - 注册缓存池服务
- `ServerRequestCacheProvider` - 注册 HTTP 缓存中间件

## 设计理念和使用场景

### 设计理念

1. **简单易用** - 通过辅助函数 `cache()` 简化缓存操作
2. **高性能** - 连接池管理和适配器复用机制
3. **灵活性** - 支持多种缓存后端和配置方式
4. **标准化** - 遵循 PSR 标准和 Symfony 组件规范

### 适用场景

#### 适合使用的场景：
- **API 响应缓存** - 缓存数据库查询结果或 API 响应
- **页面级缓存** - 缓存整个 HTTP 响应
- **配置数据缓存** - 缓存频繁读取的配置信息
- **会话数据缓存** - 存储用户会话相关数据
- **计算结果缓存** - 缓存耗时计算的结果

#### 不适合使用的场景：
- **实时性要求极高的数据** - 如股票价格、实时聊天消息
- **频繁更新的数据** - 更新频率高于缓存失效时间的数据
- **敏感数据** - 需要考虑安全性的用户隐私数据

## 项目目录结构说明

```
fastd/cache/
├── src/                           # 源代码目录
│   ├── CachePool.php             # 缓存池核心类
│   ├── Middleware/               # 中间件目录
│   │   └── XMCache.php          # HTTP 缓存中间件
│   └── ServiceProvider/          # 服务提供者目录
│       ├── CacheServiceProvider.php     # 缓存服务提供者
│       └── ServerRequestCacheProvider.php # HTTP 缓存服务提供者
├── tests/                        # 测试目录
│   ├── CachePoolTest.php        # 缓存池测试
│   ├── XMCacheIntegratedTest.php # HTTP 缓存中间件测试
│   ├── CacheServiceProviderTest.php # 服务提供者测试
│   └── cache.php                # 测试配置文件
├── docs/                         # 文档目录
│   ├── index.md                 # 文档首页
│   ├── overview.md              # 项目概述（本文档）
│   ├── api/                     # API 参考文档
│   └── installation/            # 安装使用文档
├── helpers.php                  # 辅助函数
├── composer.json               # Composer 配置
└── README.md                   # 项目说明
```

### 核心文件说明

| 文件 | 功能描述 |
|------|----------|
| `src/CachePool.php` | 缓存池管理器，负责适配器创建和连接管理 |
| `src/Middleware/XMCache.php` | HTTP 缓存中间件，实现页面级缓存 |
| `src/ServiceProvider/CacheServiceProvider.php` | 缓存服务提供者，注册缓存池到容器 |
| `src/ServiceProvider/ServerRequestCacheProvider.php` | HTTP 缓存服务提供者，注册中间件 |
| `helpers.php` | 提供全局 `cache()` 辅助函数 |

### 测试策略

项目采用分层测试策略：
- **单元测试** - 测试各个组件的独立功能
- **集成测试** - 测试组件间的协作
- **Mock 测试** - 避免外部依赖影响测试结果

### 性能特点

1. **连接复用** - CachePool 自动管理连接，避免重复创建
2. **适配器缓存** - getCache() 方法缓存适配器实例
3. **智能重连** - 自动检测连接状态并重新连接
4. **高效键生成** - 使用 CRC32 算法生成高效的缓存键

通过以上架构设计，FastD Cache 提供了一个既简单又强大的缓存解决方案，能够满足大多数 Web 应用的缓存需求。