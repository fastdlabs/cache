<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace FastD\CacheProvider\Middleware;


use DateTime;
use FastD\Http\Response;
use FastD\Middleware\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CacheMiddleware.
 */
class ServerRequestCache extends Middleware
{
    protected array $cacheParams = [];

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ('GET' !== $request->getMethod()) {
            return $handler->handle($request);
        }

        $params = $request->getQueryParams();
        // 取配置参数
        asort($params, SORT_REGULAR);
        $key = md5($request->getUri()->getPath() . '?' . http_build_query($params));
        $cache = cache('http')->getItem($key);
        if ($cache->isHit()) {
            list($content, $headers) = $cache->get();

            return new Response($content, Response::HTTP_OK, $headers);
        }

        $response = $handler->handle($request);
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return $response;
        }

        $expireAt = new DateTime();
        $expireAt->setTimestamp(time() + config()->get('cache.http.lifetime', 60));

        $response->withHeader('X-Cache', $key)->withExpires($expireAt);

        $cache->set([(string)$response->getBody(), $response->getHeaders(),]);

        cache('http')->save($cache->expiresAt($expireAt));

        return $response;
    }
}
