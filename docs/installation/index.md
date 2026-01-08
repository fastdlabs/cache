# 安装与使用指南

## 目录

- [环境要求](#环境要求)
- [安装方式](#安装方式)
- [基本配置](#基本配置)
- [缓存适配器配置](#缓存适配器配置)
- [服务提供者注册](#服务提供者注册)
- [辅助函数使用](#辅助函数使用)
- [实际应用案例](#实际应用案例)
- [常见问题解答](#常见问题解答)

---

## 环境要求

### 系统要求

- **PHP 版本**: >= 8.2
- **操作系统**: Linux, macOS, Windows
- **内存**: 建议 >= 256MB

### PHP 扩展依赖

```bash
# 必需扩展
php-json
php-mbstring

# 可选扩展（根据使用的适配器而定）
php-redis        # Redis 适配器
php-memcached    # Memcached 适配器
```

### Composer 依赖

项目会自动安装以下依赖包：

```json
{
    "symfony/cache": "^8.0",     // Symfony 缓存组件
}
```

---

## 安装方式

### 1. Composer 安装（推荐）

```bash
composer require fastd/cache
```

### 2. 手动安装

```bash
# 克隆仓库
git clone https://github.com/fastdlabs/cache.git

# 进入项目目录
cd cache

# 安装依赖
composer install
```

### 3. 开发版本安装

```bash
composer require fastd/cache:dev-develop
```

---

## 基本配置

### 1. 创建配置文件

在项目 `config/` 目录下创建 `cache.php` 配置文件：

```php
<?php

return [
    // 文件系统缓存配置
    'file' => [
        'adapter' => [
            'class' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
        ],
        'namespace' => 'myapp',
        'lifetime' => 3600,
        'directory' => __DIR__ . '/../runtime/cache/',
    ],
    
    // HTTP 缓存中间件配置
    'httpCache' => [
        'enable' => true,
        'adapter' => [
            'class' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
        ],
        'lifetime' => 60,
        'directory' => __DIR__ . '/../runtime/http/',
        'cache_keys' => ['page', 'limit', 'sort'],
    ],
];
```

### 2. 配置项说明

| 配置项 | 类型 | 必需 | 说明 |
|--------|------|------|------|
| `adapter.class` | string | 是 | 适配器类名 |
| `adapter.dsn` | string | 否 | 连接字符串 |
| `adapter.options` | array | 否 | 连接选项 |
| `namespace` | string | 否 | 缓存命名空间 |
| `lifetime` | int | 否 | 默认过期时间（秒） |
| `directory` | string | 否 | 文件缓存目录 |

---

## 缓存适配器配置

### 文件系统适配器

#### 基础配置

```php
'file' => [
    'adapter' => [
        'class' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
    ],
    'namespace' => 'myapp',
    'lifetime' => 3600,
    'directory' => '/tmp/cache/app/',
],
```

#### PHP 文件适配器（更高性能）

```php
'phpfile' => [
    'adapter' => [
        'class' => \Symfony\Component\Cache\Adapter\PhpFilesAdapter::class,
    ],
    'namespace' => 'myapp',
    'lifetime' => 3600,
    'directory' => '/tmp/cache/php/',
],
```

### Redis 适配器

#### 基础 Redis 配置

```php
'redis' => [
    'adapter' => [
        'class' => \Symfony\Component\Cache\Adapter\RedisAdapter::class,
        'dsn' => 'redis://localhost:6379/0',
    ],
    'namespace' => 'myapp',
    'lifetime' => 3600,
],
```

#### 带认证的 Redis 配置

```php
'redis_auth' => [
    'adapter' => [
        'class' => \Symfony\Component\Cache\Adapter\RedisAdapter::class,
        'dsn' => 'redis://username:password@localhost:6379/1',
        'options' => [
            'timeout' => 30,
            'read_timeout' => 10,
            'retry_interval' => 5,
        ],
    ],
],
```

#### Redis 集群配置

```php
'redis_cluster' => [
    'adapter' => [
        'class' => \Symfony\Component\Cache\Adapter\RedisAdapter::class,
        'dsn' => 'redis:?host[localhost]&host[localhost:6380]&host[localhost:6381]',
        'options' => [
            'redis_cluster' => true,
            'timeout' => 30,
        ],
    ],
],
```

### Memcached 适配器

```php
'memcache' => [
    'adapter' => [
        'class' => \Symfony\Component\Cache\Adapter\MemcachedAdapter::class,
        'dsn' => 'memcached://localhost:11211',
        'options' => [
            'libketama_compatible' => true,
            'serializer' => 'igbinary',
            'compression' => true,
        ],
    ],
    'namespace' => 'myapp',
    'lifetime' => 1800,
],
```

### 多适配器混合配置

```php
return [
    // 热点数据使用 Redis
    'hot_data' => [
        'adapter' => [
            'class' => \Symfony\Component\Cache\Adapter\RedisAdapter::class,
            'dsn' => 'redis://localhost:6379/0',
        ],
        'lifetime' => 300, // 5分钟
    ],
    
    // 冷数据使用文件系统
    'cold_data' => [
        'adapter' => [
            'class' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
        ],
        'lifetime' => 86400, // 24小时
        'directory' => '/tmp/cache/cold/',
    ],
    
    // 会话数据使用 Memcached
    'session' => [
        'adapter' => [
            'class' => \Symfony\Component\Cache\Adapter\MemcachedAdapter::class,
            'dsn' => 'memcached://localhost:11211',
        ],
        'lifetime' => 1800, // 30分钟
    ],
];
```

---

## 服务提供者注册

### 1. 基础缓存服务注册

编辑 `app.php` 配置文件：

```php
<?php

return [
    // ... 其他配置
    
    'services' => [
        // 注册基础缓存服务
        \FastD\Cache\ServiceProvider\CacheServiceProvider::class,
        
        // 可选：注册 HTTP 缓存中间件
        \FastD\Cache\ServiceProvider\ServerRequestCacheProvider::class,
    ],
    
    // ... 其他配置
];
```

### 2. 条件注册

```php
<?php

$services = [
    \FastD\Cache\ServiceProvider\CacheServiceProvider::class,
];

// 根据环境条件注册 HTTP 缓存
if (config('app.env') === 'production') {
    $services[] = \FastD\Cache\ServiceProvider\ServerRequestCacheProvider::class;
}

return [
    'services' => $services,
];
```

### 3. 自定义服务提供者

```php
<?php

class CustomCacheServiceProvider implements ServiceProviderInterface 
{
    public function register(Container $container): void 
    {
        // 自定义缓存配置
        $config = [
            'custom_cache' => [
                'adapter' => [
                    'class' => FilesystemAdapter::class,
                ],
                'lifetime' => 7200,
            ]
        ];
        
        $cachePool = new CachePool($config);
        $container->add('custom_cache', $cachePool);
    }
}
```

---

## 辅助函数使用

### 1. 基础使用

```php
<?php

// 获取缓存适配器
$cache = cache('file');

// 存储数据
$item = $cache->getItem('user_123');
$item->set([
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com'
])->expiresAfter(3600);

$cache->save($item);

// 读取数据
$item = $cache->getItem('user_123');
if ($item->isHit()) {
    $user = $item->get();
    echo "用户名: " . $user['name'];
} else {
    echo "缓存未命中";
}
```

### 2. 在控制器中使用

```php
<?php

class UserController extends Controller 
{
    public function show($id) 
    {
        // 尝试从缓存获取用户数据
        $cache = cache('redis');
        $cacheKey = "user:{$id}";
        
        $item = $cache->getItem($cacheKey);
        if ($item->isHit()) {
            return json($item->get());
        }
        
        // 缓存未命中，查询数据库
        $user = $this->userRepository->find($id);
        if (!$user) {
            return json(['error' => 'User not found'], 404);
        }
        
        // 存储到缓存
        $item->set($user->toArray())->expiresAfter(1800);
        $cache->save($item);
        
        return json($user);
    }
    
    public function update($id) 
    {
        // 更新用户数据
        $user = $this->userRepository->update($id, $request->all());
        
        // 清除相关缓存
        $cache = cache('redis');
        $cache->deleteItem("user:{$id}");
        $cache->deleteItem("user:list");
        
        return json($user);
    }
}
```

### 3. 在服务类中使用

```php
<?php

class UserService 
{
    public function getUserProfile($userId) 
    {
        $cache = cache('redis');
        $key = "user:{$userId}:profile";
        
        return $this->getCachedData($key, function() use ($userId) {
            return $this->buildUserProfile($userId);
        }, 3600);
    }
    
    public function getUserPosts($userId, $page = 1) 
    {
        $cache = cache('redis');
        $key = "user:{$userId}:posts:page:{$page}";
        
        return $this->getCachedData($key, function() use ($userId, $page) {
            return $this->userRepository->getUserPosts($userId, $page);
        }, 1800);
    }
    
    private function getCachedData($key, callable $callback, $ttl) 
    {
        $cache = cache('redis');
        $item = $cache->getItem($key);
        
        if ($item->isHit()) {
            return $item->get();
        }
        
        $data = $callback();
        $item->set($data)->expiresAfter($ttl);
        $cache->save($item);
        
        return $data;
    }
}
```

### 4. 批量操作

```php
<?php

// 批量获取缓存项
$cache = cache('redis');
$keys = ['user:1', 'user:2', 'user:3'];
$items = $cache->getItems($keys);

foreach ($items as $key => $item) {
    if ($item->isHit()) {
        echo "{$key}: " . json_encode($item->get()) . "\n";
    } else {
        echo "{$key}: 缓存未命中\n";
    }
}

// 批量删除缓存
$cache->deleteItems(['user:1', 'user:2', 'user:3']);

// 清空整个缓存池
$cache->clear();
```

---

## 实际应用案例

### 案例1：API 响应缓存

```php
<?php

class ApiController extends Controller 
{
    public function getUsers(Request $request) 
    {
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $sortBy = $request->query('sort', 'created_at');
        
        $cache = cache('redis');
        $cacheKey = "users:list:page:{$page}:limit:{$limit}:sort:{$sortBy}";
        
        $item = $cache->getItem($cacheKey);
        if ($item->isHit()) {
            return json($item->get());
        }
        
        // 查询数据库
        $users = $this->userRepository->paginate($page, $limit, $sortBy);
        
        // 缓存结果
        $responseData = [
            'data' => $users->toArray(),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $users->count()
            ]
        ];
        
        $item->set($responseData)->expiresAfter(300); // 5分钟缓存
        $cache->save($item);
        
        return json($responseData);
    }
}
```

### 案例2：配置数据缓存

```php
<?php

class ConfigService 
{
    public function getSystemConfig($key = null) 
    {
        $cache = cache('file');
        $item = $cache->getItem('system_config');
        
        if (!$item->isHit()) {
            // 从数据库加载配置
            $configs = $this->configRepository->getAllConfigs();
            $configArray = [];
            
            foreach ($configs as $config) {
                $configArray[$config->key] = $config->value;
            }
            
            $item->set($configArray)->expiresAfter(3600);
            $cache->save($item);
        }
        
        $allConfigs = $item->get();
        
        if ($key) {
            return $allConfigs[$key] ?? null;
        }
        
        return $allConfigs;
    }
    
    public function clearConfigCache() 
    {
        cache('file')->deleteItem('system_config');
    }
}
```

### 案例3：会话数据缓存

```php
<?php

class SessionService 
{
    public function setUserSession($userId, $sessionData) 
    {
        $cache = cache('memcache');
        $key = "session:user:{$userId}";
        
        $item = $cache->getItem($key);
        $item->set($sessionData)->expiresAfter(1800); // 30分钟
        $cache->save($item);
        
        return $key;
    }
    
    public function getUserSession($userId) 
    {
        $cache = cache('memcache');
        $key = "session:user:{$userId}";
        
        $item = $cache->getItem($key);
        return $item->isHit() ? $item->get() : null;
    }
    
    public function clearUserSession($userId) 
    {
        $cache = cache('memcache');
        $key = "session:user:{$userId}";
        $cache->deleteItem($key);
    }
}
```

---

## 常见问题解答

### Q1: 如何选择合适的缓存适配器？

**A:** 根据使用场景选择：

- **文件系统**: 适合小型应用，无需额外服务
- **Redis**: 适合高性能、分布式场景
- **Memcached**: 适合简单的键值缓存
- **PHP文件**: 适合高频读取的小数据

### Q2: 缓存未生效怎么办？

**A:** 检查以下几点：
```php
// 1. 确认服务提供者已注册
var_dump(class_exists(\FastD\Cache\ServiceProvider\CacheServiceProvider::class));

// 2. 检查配置是否正确加载
var_dump(config('cache'));

// 3. 测试缓存功能
$cache = cache('file');
$item = $cache->getItem('test');
$item->set('value')->expiresAfter(60);
$cache->save($item);

$cachedItem = $cache->getItem('test');
var_dump($cachedItem->isHit()); // 应该返回 true
```

### Q3: 如何处理缓存击穿问题？

**A:** 使用互斥锁或预热策略：
```php
public function getDataWithMutex($key) 
{
    $cache = cache('redis');
    $item = $cache->getItem($key);
    
    if ($item->isHit()) {
        return $item->get();
    }
    
    // 使用分布式锁
    $lockKey = "lock:{$key}";
    if ($cache->getItem($lockKey)->isHit()) {
        // 等待其他进程加载数据
        sleep(1);
        return $this->getDataWithMutex($key);
    }
    
    // 获取锁
    $lockItem = $cache->getItem($lockKey);
    $lockItem->set(true)->expiresAfter(30);
    $cache->save($lockItem);
    
    try {
        // 加载数据
        $data = $this->loadExpensiveData($key);
        $item->set($data)->expiresAfter(3600);
        $cache->save($item);
        return $data;
    } finally {
        // 释放锁
        $cache->deleteItem($lockKey);
    }
}
```

### Q4: 缓存键如何设计比较好？

**A:** 遵循以下原则：
```php
// 好的设计
$userProfileKey = "user:{$userId}:profile:v1";
$userPostsKey = "user:{$userId}:posts:page:{$page}:v1";

// 避免的设计
$key1 = md5("user_profile_" . $userId);
$key2 = "cache_" . time() . "_" . $userId;
```

### Q5: 如何监控缓存性能？

**A:** 可以添加监控代码：
```php
class MonitoredCache 
{
    public function getWithMetrics($key) 
    {
        $startTime = microtime(true);
        $cache = cache('redis');
        $item = $cache->getItem($key);
        
        $metrics = [
            'key' => $key,
            'hit' => $item->isHit(),
            'duration' => microtime(true) - $startTime,
            'timestamp' => time()
        ];
        
        // 记录指标到日志或监控系统
        $this->logMetrics($metrics);
        
        return $item;
    }
}
```

### Q6: 生产环境中需要注意什么？

**A:** 生产环境建议：

1. **连接池配置**
```php
'redis' => [
    'adapter' => [
        'class' => RedisAdapter::class,
        'dsn' => 'redis://localhost:6379/0',
        'options' => [
            'persistent' => true,
            'timeout' => 5,
            'read_timeout' => 3,
        ],
    ],
],
```

2. **监控和告警**
```php
// 定期检查缓存健康状态
$scheduler->call(function() {
    $cache = cache('redis');
    try {
        $item = $cache->getItem('health_check');
        $item->set(time())->expiresAfter(60);
        $cache->save($item);
    } catch (Exception $e) {
        // 发送告警
        $this->sendAlert('Cache connection failed: ' . $e->getMessage());
    }
})->everyMinute();
```

3. **备份策略**
```php
// 定期备份重要缓存数据
$scheduler->call(function() {
    $this->backupCriticalCache();
})->daily();
```

通过以上配置和使用指南，您可以充分发挥 FastD Cache 的强大功能，提升应用性能和用户体验。