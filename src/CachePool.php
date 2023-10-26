<?php

namespace FastD\CacheProvider;

class CachePool
{
    /**
     * @var AbstractAdapter[]
     */
    protected $caches = [];

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $redises = [];

    /**
     * Cache constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function connect($key)
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

    public function getCache($key)
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

    public function initPool()
    {
        foreach ($this->config as $name => $config) {
            $this->getCache($name);
        }
    }

    protected function getRedisAdapter(array $config, $key)
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
            $cache = new FilesystemAdapter('', 0, '/tmp/cache');
        }

        $this->redises[$key] = [
            'connect' => $connect,
            'driver' => RedisAdapter::class,
        ];

        return $cache;
    }

    protected function getAdapter(array $config)
    {
        return new $config['adapter'](
            $config['params']['namespace'] ?? '',
            $config['params']['lifetime'] ?? '',
            $config['params']['directory'] ?? app()->getPath() . '/runtime/cache'
        );
    }
}
