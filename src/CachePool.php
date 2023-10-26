<?php

namespace FastD\CacheProvider;

use ReflectionClass;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CachePool
{
    protected array $caches = [];

    /**
     * @var array
     */
    protected array $config;

    /**
     * @var array
     */
    protected array $redises = [];

    /**
     * Cache constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initConnections(): void
    {
        foreach ($this->config as $name => $config) {
            $this->getCache($name);
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function connect($key): AbstractAdapter
    {
        if (!isset($this->config[$key])) {
            throw new \LogicException(sprintf('No set %s cache', $key));
        }
        $config = $this->config[$key];
        // 解决使用了自定义的 RedisAdapter 时无法正常创建的问题
        if (
            $config['adapter'] === RedisAdapter::class
            || (new ReflectionClass($config['adapter']))->isSubclassOf(RedisAdapter::class)) {
            return $this->getRedisAdapter($config, $key);
        }
        return $this->getAdapter($config);
    }

    public function getCache(string $key): AbstractAdapter
    {
        if (!isset($this->caches[$key])) {
            $this->caches[$key] = $this->connect($key);
        }

        if (isset($this->redises[$key])) {
            if (
                null === $this->redises[$key]['connect']
                || false === $this->redises[$key]['connect']->ping()
            ) {
                $this->caches[$key] = $this->connect($key);
            }
        }

        return $this->caches[$key];
    }

    protected function getRedisAdapter(array $config, $key): AbstractAdapter
    {
        $connect = null;
        try {
            $connect = RedisAdapter::createConnection($config['params']['dsn']);
            $cache = new $config['adapter'](
                $connect,
                $config['params']['namespace'] ?? '',
                $config['params']['lifetime'] ?? ''
            );
        } catch (\Exception $e) {
            throw $e;
        }

        $this->redises[$key] = [
            'connect' => $connect,
            'driver' => RedisAdapter::class,
        ];

        return $cache;
    }

    protected function getAdapter(array $config): AbstractAdapter
    {
        return new $config['adapter'](
            $config['params']['namespace'] ?? '',
            $config['params']['lifetime'] ?? 0,
            $config['params']['directory'] ?? '/tmp/cache'
        );
    }
}
