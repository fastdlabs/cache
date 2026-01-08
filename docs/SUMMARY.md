# FastD Cache 文档索引

## 📚 文档结构概览

```
docs/
├── index.md              # 文档首页 - 快速入门和导航
├── overview.md           # 项目概述 - 架构、特性和设计理念
├── api/                  # API 参考文档
│   └── index.md         # 核心类和方法详细说明
└── installation/         # 安装与使用指南
    └── index.md         # 安装配置、使用示例和最佳实践
```

## 🚀 快速导航

### [首页 (index.md)](index.md)
- 项目简介和主要特性
- 快速开始指南
- 文档导航目录

### [项目概述 (overview.md)](overview.md)
- **项目简介和核心功能**
  - 多层缓存架构介绍
  - 支持的缓存适配器类型
- **技术架构和依赖说明**
  - 核心架构图解
  - 主要依赖组件
  - 核心组件关系说明
- **设计理念和使用场景**
  - 四大设计原则
  - 适用和不适用场景
- **项目目录结构说明**
  - 完整目录结构解析
  - 核心文件功能说明
  - 测试策略介绍

### [API 参考 (api/index.md)](api/index.md)
- **CachePool 类**
  - 类签名和构造函数
  - 主要方法详解 (`getCache`, `getAdapter`, `connect`, `initConnections`)
  - 配置选项说明
  - 支持的适配器类型
  - 连接池机制
- **XMCache 中间件**
  - 中间件常量定义
  - 核心方法 (`process`, `generateCacheKey`, `cacheResponse`)
  - 配置选项和缓存控制
  - 自定义缓存键支持
- **服务提供者**
  - CacheServiceProvider 和 ServerRequestCacheProvider
  - 服务注册机制
- **辅助函数**
  - `cache()` 函数使用指南
  - 使用场景和注意事项
- **最佳实践**
  - 缓存键命名规范
  - 缓存策略模板
  - 错误处理示例

### [安装与使用 (installation/index.md)](installation/index.md)
- **环境要求**
  - 系统和 PHP 版本要求
  - 必需和可选 PHP 扩展
  - Composer 依赖说明
- **安装方式**
  - Composer 安装（推荐）
  - 手动安装
  - 开发版本安装
- **基本配置**
  - 配置文件创建
  - 配置项详细说明
- **缓存适配器配置**
  - 文件系统适配器配置
  - Redis 适配器配置（含集群和认证）
  - Memcached 适配器配置
  - 多适配器混合配置示例
- **服务提供者注册**
  - 基础注册方法
  - 条件注册示例
  - 自定义服务提供者
- **辅助函数使用**
  - 基础使用示例
  - 控制器中使用
  - 服务类中使用
  - 批量操作示例
- **实际应用案例**
  - API 响应缓存
  - 配置数据缓存
  - 会话数据缓存
- **常见问题解答**
  - 缓存适配器选择指南
  - 缓存故障排除
  - 缓存击穿处理
  - 缓存键设计原则
  - 性能监控方法
  - 生产环境最佳实践

## 🎯 使用建议

### 新手入门路径
1. 阅读 [首页](index.md) 了解项目概况
2. 查看 [项目概述](overview.md) 理解架构设计
3. 按照 [安装指南](installation/index.md) 进行安装配置
4. 参考 [API 文档](api/index.md) 学习具体使用方法

### 进阶学习路径
1. 深入研究 [API 参考](api/index.md) 中的最佳实践
2. 学习 [安装文档](installation/index.md) 中的高级配置
3. 参考实际应用案例实现复杂业务场景
4. 掌握常见问题的解决方案

### 故障排查路径
1. 首先查看 [常见问题解答](installation/index.md#常见问题解答)
2. 检查 [API 文档](api/index.md) 中的相关方法说明
3. 参考 [配置说明](installation/index.md#基本配置) 确认配置正确性
4. 查阅 [项目概述](overview.md) 确认使用场景是否合适

## 📖 文档维护

本文档系统将持续更新和完善，如果您发现任何问题或有更好的建议，请通过以下方式反馈：

- 提交 GitHub Issue
- 发送邮件至 bboyjanhuang@gmail.com
- 在微博 @编码侠 反馈

---
*最后更新时间：2024年*