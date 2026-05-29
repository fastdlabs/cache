<?php

declare(strict_types=1);

namespace FastD\Cache\Middleware;

use Symfony\Component\Cache\Adapter\AbstractAdapter;
use FastD\Middleware\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use FastD\Http\Response\StatusCode;
use FastD\Http\Response\Text;
use DateTime;

class XMCache extends Middleware
{
    const CacheName = 'xmCache';
    
    const HeaderKey = 'X-M-Cache';

    const HeaderStatusKey = 'X-M-Cache-Status';

    protected array $cacheParams = [];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 非 GET 请求不缓存
        if ('GET' !== $request->getMethod()) {
            return $handler->handle($request);
        }

        // 取配置参数，version 为缓存版本，可通过设置版本进行缓存开关或者其他处理
        $version = 1;
        $config = config()->parsed->get('cache.httpCache', []);
        $cache = cache(static::CacheName);

        // 如果存在请求缓存 key，优先判断
        $requestCacheKey = $request->getHeaderLine(static::HeaderKey);
        if ($requestCacheKey !== '') {
            // 严格匹配请求中的缓存键
            $cachedResponse = $this->getFromCache($cache, $requestCacheKey);
            if ($cachedResponse !== null) {
                return $cachedResponse;
            }
        }

        $generatedCacheKey = $this->generateCacheKey($request, $config, $version);
        $cachedResponse = $this->getFromCache($cache, $generatedCacheKey);
        if ($cachedResponse !== null) {
            // 缓存命中，返回缓存响应，但不添加新的缓存键
            return $cachedResponse;
        }

        $response = $handler->handle($request);
        // 非 200 不缓存
        if (StatusCode::HTTP_OK !== $response->getStatusCode()) {
            return $response;
        }

        return $this->cacheResponse($request, $response, $cache, $generatedCacheKey, $config);
    }

    private function getFromCache(AbstractAdapter $cache, string $cacheKey): ?ResponseInterface
    {
        $cached = $cache->getItem($cacheKey);

        if (!$cached->isHit()) {
            return null;
        }

        [$content, $headers] = $cached->get();

        // 验证缓存数据的完整性
        if (!is_string($content) || !is_array($headers)) {
            $cache->deleteItem($cacheKey);
            return null;
        }

        $cacheResponse = new Text(StatusCode::HTTP_OK, $content, $headers);
        return $cacheResponse->withHeader(static::HeaderStatusKey, 'HIT');
    }

    private function generateCacheKey(ServerRequestInterface $request, array $config, int $version): string
    {
        $path = $request->getUri()->getPath();
        $paramHash = '';

        if (isset($config['cache_keys']) && is_array($config['cache_keys'])) {
            // 只取配置中指定的查询参数
            $allParams = $request->getQueryParams();
            $filteredParams = array_intersect_key($allParams, array_flip($config['cache_keys']));

            // 排序确保参数顺序一致
            ksort($filteredParams, SORT_STRING);

            $paramHash = http_build_query($filteredParams, '', '&', PHP_QUERY_RFC3986);
        }

        // 使用路径和参数生成缓存键
        $keySource = $path . ($paramHash ? '?' . $paramHash : '') . $version;
        return 'mc' . sprintf("%u", crc32($keySource));
    }

    private function cacheResponse(
        ServerRequestInterface $request,
        ResponseInterface $response,
        AbstractAdapter $cache,
        string $cacheKey,
        array $config
    ): ResponseInterface
    {
        // 设置缓存过期时间
        $lifetime = $config['lifetime'] ?? 60;
        $expireAt = new DateTime();
        $expireAt->modify("+{$lifetime} seconds");
        $expireAt->setTimezone(new \DateTimeZone('GMT'));

        // Format Expires header according to RFC 7231 (always in GMT)
        $expiresFormatted = $expireAt->format('D, d M Y H:i:s') . ' GMT';
        
        $response = $response->withHeader(static::HeaderKey, $cacheKey)->withHeader('Expires', $expiresFormatted);

        // 缓存响应数据
        $cacheData = [
            (string)$response->getBody(),
            $response->getHeaders()
        ];

        $item = $cache->getItem($cacheKey)->set($cacheData)->expiresAt($expireAt);
        $cache->save($item);

        // 添加缓存键到响应头，用于下次请求
        return $response->withHeader(static::HeaderStatusKey, 'MISS'); // 标识缓存状态
    }
}
