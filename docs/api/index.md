# API 参考文档

## 目录

- [CachePool 类](#cachepool-类) - 缓存池核心类
- [XMCache 中间件](#xmcache-中间件) - HTTP 缓存中间件
- [服务提供者](#服务提供者) - 服务注册和容器集成
- [辅助函数](#辅助函数) - 全局便捷函数

---

## CachePool 类

`FastD\Cache\CachePool` 是缓存系统的核心管理类，负责管理各种缓存适配器的创建、连接和生命周期。

### 类签名

```php
class CachePool implements CallbackEventsInterface
```

### 构造函数

```php
public function __construct(array $config)
```

**参数:**
- `$config` (array) - 缓存配置数组，包含各种适配器的配置信息

**示例:**
```php
$config = [
    'file' => [
        'adapter' => [
            'class' => FilesystemAdapter::class,
        ],
        'namespace' => 'my_app',
        'lifetime' => 3600,
        'directory' => '/tmp/cache'
    ]
];

$cachePool = new CachePool($config);
```

### 主要方法

#### getCache()

```php
public function getCache(string $key): AbstractAdapter
```

获取指定名称的缓存适配器实例，支持适配器复用。

**参数:**
- `$key` (string) - 缓存配置键名

**返回值:**
- `AbstractAdapter` - Symfony Cache 适配器实例

**示例:**
```php
$cache = $cachePool->getCache('file');
$item = $cache->getItem('my_key');
if (!$item->isHit()) {
    $item->set('cached_value')->expiresAfter(3600);
    $cache->save($item);
}
```

#### getAdapter()

```php
public function getAdapter(string $key): AbstractAdapter
```

创建新的缓存适配器实例（不复用已有实例）。

**参数:**
- `$key` (string) - 缓存配置键名

**返回值:**
- `AbstractAdapter` - 新的 Symfony Cache 适配器实例

#### connect()

```php
public function connect(string $key): mixed
```

建立到缓存服务器的连接。

**参数:**
- `$key` (string) - 缓存配置键名

**返回值:**
- `mixed` - 连接对象（Redis 连接、Memcached 连接等）

**异常:**
- `ErrorException` - 当配置不存在时抛出
- `InvalidArgumentException` - 当 DSN 方案不支持时抛出

#### initConnections()

```php
public function initConnections(): void
```

初始化所有配置了 DSN 的连接。

#### onCallback()

```php
public function onCallback(): bool
```

回调事件处理器，初始化连接。

**返回值:**
- `bool` - 总是返回 `true`

### 配置选项

CachePool 支持以下配置选项：

```php
[
    'adapter_name' => [
        'adapter' => [
            'class' => AdapterClass::class,    // 适配器类名
            'dsn' => 'connection_dsn',         // 连接字符串（可选）
            'options' => []                    // 连接选项（可选）
        ],
        'namespace' => 'cache_namespace',      // 缓存命名空间
        'lifetime' => 3600,                    // 默认生存时间（秒）
        'directory' => '/path/to/cache'        // 文件缓存目录（文件适配器）
    ]
]
```

### 支持的适配器

#### 文件系统适配器
```php
'file' => [
    'adapter' => [
        'class' => FilesystemAdapter::class,
    ],
    'namespace' => 'app',
    'lifetime' => 3600,
    'directory' => '/tmp/cache'
]
```

#### Redis 适配器
```php
'redis' => [
    'adapter' => [
        'class' => RedisAdapter::class,
        'dsn' => 'redis://localhost:6379/0',
        'options' => [
            'timeout' => 30,
            'read_timeout' => 10
        ]
    ],
    'namespace' => 'app'
]
```

#### Memcached 适配器
```php
'memcache' => [
    'adapter' => [
        'class' => MemcachedAdapter::class,
        'dsn' => 'memcached://localhost:11211',
        'options' => [
            'libketama_compatible' => true
        ]
    ]
]
```

### 连接池机制

CachePool 内置连接池管理：

```php
// 连接会被自动复用
$connection1 = $cachePool->connect('redis');
$connection2 = $cachePool->connect('redis');
// $connection1 === $connection2 （同一个连接对象）

// 自动连接健康检查
// 如果连接失效会自动重新建立连接
```

---

## XMCache 中间件

`FastD\Cache\Middleware\XMCache` 是一个 PSR-15 HTTP 中间件，提供页面级缓存功能。

### 类签名

```php
class XMCache extends Middleware
```

### 常量定义

```php
const CacheName = 'httpCache';           // 缓存配置名称
const HeaderKey = 'X-M-Cache';           // 缓存键头部
const HeaderStatusKey = 'X-M-Cache-Status'; // 缓存状态头部
```

### 主要方法

#### process()

```php
public function process(
    ServerRequestInterface $request, 
    RequestHandlerInterface $handler
): ResponseInterface
```

处理 HTTP 请求并应用缓存逻辑。

**缓存规则:**
- 仅缓存 GET 请求
- 仅缓存 200 状态码的响应
- 基于 URL 路径和查询参数生成缓存键

**示例:**
```php
// 中间件会自动处理缓存逻辑
$response = $middleware->process($request, $handler);
// 如果缓存命中，返回缓存的响应
// 如果缓存未命中，执行后续处理器并将结果缓存
```

#### generateCacheKey()

```php
private function generateCacheKey(
    ServerRequestInterface $request, 
    array $config, 
    int $version
): string
```

生成缓存键。

**算法:**
1. 获取请求路径
2. 根据配置过滤查询参数
3. 使用 CRC32 算法生成哈希值
4. 添加版本号防止缓存污染

**示例:**
```php
// /api/users?page=1&limit=10 生成类似 'mh123456789' 的键
$cacheKey = $this->generateCacheKey($request, $config, 1);
```

#### cacheResponse()

```php
private function cacheResponse(
    ServerRequestInterface $request,
    ResponseInterface $response,
    AbstractAdapter $cache,
    string $cacheKey,
    array $config
): ResponseInterface
```

缓存响应数据。

**缓存内容:**
- 响应体内容
- 响应头部信息
- 过期时间

**添加的响应头部:**
- `X-M-Cache`: 缓存键
- `X-M-Cache-Status`: MISS（表示缓存未命中）
- `Expires`: 过期时间

### 配置选项

HTTP 缓存中间件支持以下配置：

```php
'httpCache' => [
    'enable' => true,                    // 是否启用
    'lifetime' => 60,                    // 缓存生存时间（秒）
    'cache_keys' => ['page', 'limit'],   // 参与缓存键生成的查询参数
    'adapter' => [
        'class' => FilesystemAdapter::class,
    ]
]
```

### 缓存控制头部

中间件会在响应中添加以下头部：

```
X-M-Cache: mh123456789
X-M-Cache-Status: HIT|MISS
Expires: Wed, 21 Oct 2015 07:28:00 GMT
```

### 自定义缓存键

可以通过请求头部指定自定义缓存键：

```php
// 客户端可以在请求中添加 X-M-Cache 头部
$request = $request->withHeader('X-M-Cache', 'custom_cache_key');
```

---

## 服务提供者

### CacheServiceProvider

`FastD\Cache\ServiceProvider\CacheServiceProvider` 负责注册缓存池服务到容器。

#### register()

```php
public function register(Container $container): void
```

注册缓存服务。

**注册的服务:**
- `cache`: CachePool 实例
- `onWorkerStart`: 连接初始化回调

**示例:**
```php
// app.php 配置文件
return [
    'services' => [
        \FastD\Cache\ServiceProvider\CacheServiceProvider::class,
    ]
];
```

### ServerRequestCacheProvider

`FastD\Cache\ServiceProvider\ServerRequestCacheProvider` 负责注册 HTTP 缓存中间件。

#### register()

```php
public function register(Container $container): void
```

注册 HTTP 缓存中间件到调度器。

**示例:**
```php
// app.php 配置文件
return [
    'services' => [
        \FastD\Cache\ServiceProvider\ServerRequestCacheProvider::class,
    ]
];
```

---

## 辅助函数

### cache()

```php
function cache(string $name): AbstractAdapter
```

获取缓存适配器的全局辅助函数。

**参数:**
- `$name` (string) - 缓存配置名称

**返回值:**
- `AbstractAdapter` - 缓存适配器实例

**异常:**
- `Exception` - 当缓存服务未注册时抛出

**示例:**
```php
// 简单使用
$cache = cache('file');
$item = $cache->getItem('user_123');
if (!$item->isHit()) {
    $data = getUserData(123);
    $item->set($data)->expiresAfter(3600);
    $cache->save($item);
}

// 在控制器中使用
public function getUser($id) {
    $cache = cache('redis');
    $key = "user_{$id}";
    
    $item = $cache->getItem($key);
    if ($item->isHit()) {
        return $item->get();
    }
    
    $user = $this->userRepository->find($id);
    $item->set($user)->expiresAfter(1800);
    $cache->save($item);
    
    return $user;
}
```

### 使用场景

1. **简单的缓存操作** - 快速访问缓存而不必通过容器
2. **控制器中的缓存** - 在业务逻辑中方便地使用缓存
3. **服务层缓存** - 在服务类中缓存计算结果或数据库查询

### 注意事项

- 确保 CacheServiceProvider 已经注册
- 缓存键应该具有良好的唯一性和描述性
- 合理设置缓存过期时间
- 注意缓存数据的一致性问题

---

## 最佳实践

### 缓存键命名规范

```php
// 推荐的命名方式
$userCache = cache('redis');
$item = $userCache->getItem("user:{$userId}:profile");
$item = $userCache->getItem("post:{$postId}:comments:page:{$page}");

// 避免的命名方式
$item = $userCache->getItem("u{$userId}");
$item = $userCache->getItem(md5($complexString));
```

### 缓存策略

```php
// 读取缓存
function getCachedData($key, $callback, $ttl = 3600) {
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

// 使用示例
$user = getCachedData(
    "user:{$id}", 
    fn() => $this->userRepository->find($id),
    1800
);
```

### 错误处理

```php
try {
    $cache = cache('redis');
    $item = $cache->getItem('critical_data');
    
    if (!$item->isHit()) {
        // 如果缓存不可用，直接查询数据库
        return $this->databaseQuery();
    }
    
    return $item->get();
} catch (Exception $e) {
    // 缓存服务异常时的降级处理
    return $this->databaseQuery();
}
```