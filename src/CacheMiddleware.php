<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace FastD\CacheProvider;


use FastD\Http\Response;
use FastD\Middleware\DelegateInterface;
use FastD\Middleware\Middleware;
use FastD\Utils\DateObject;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class CacheMiddleware.
 */
class CacheMiddleware extends Middleware
{
    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $next
     * @return Response|\Psr\Http\Message\ResponseInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function handle(ServerRequestInterface $request, DelegateInterface $next)
    {
        if ('GET' !== $request->getMethod()) {
            return $next->process($request);
        }

        $params = $request->getQueryParams();

        $this->sortQueryParams($params);

        $key = md5($request->getUri()->getPath().'?'.http_build_query($params));
        $cache = cache()->getItem($key);
        if ($cache->isHit()) {
            list($content, $headers) = $cache->get();

            return new Response($content, Response::HTTP_OK, $headers);
        }

        $response = $next->process($request);
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return $response;
        }

        $expireAt = DateObject::makeFromTimestamp(time() + config()->get('common.cache.lifetime', 60));

        $response->withHeader('X-Cache', $key)->withExpires($expireAt);

        $cache->set([
            (string) $response->getBody(),
            $response->getHeaders(),
        ]);

        cache()->save($cache->expiresAt($expireAt));

        return $response;
    }

    /**
     * @param array $arr
     */
    protected function sortQueryParams(array &$arr)
    {
        ksort($arr);
        array_walk($arr, function (&$item) {
            if (is_array($item)) {
                $this->sortQueryParams($item);
            }
        });
    }
}
