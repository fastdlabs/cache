<?php

declare(strict_types=1);

namespace FastD\Cache;

use ErrorException;
use FastD\Server\Events\CallbackEventsInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Throwable;

class CachePool implements CallbackEventsInterface
{
    protected array $caches = [];

    protected array $connections = [];

    public function __construct(protected array $config)
    {
    }

    public function initConnections(): void
    {
        foreach ($this->config as $key => $config) {
            if (isset($config['adapter']['dsn'])) {
                $this->connect($key);
            }
        }
    }

    public function connect(string $key): mixed
    {
        if (!isset($this->config[$key])) {
            throw new ErrorException(sprintf('No set %s cache config.', $key));
        }

        $config = $this->config[$key]['adapter'];
        $dsn = $config['dsn'];
        $options = $config['options'] ?? [];

        if (isset($this->connections[$key])) {
            $connection = $this->connections[$key];

            $needsReconnect = match (true) {
                $connection instanceof RedisAdapter => !$connection->ping(),
                $connection instanceof MemcachedAdapter => !$connection->getStats(),
                default => false
            };

            if ($needsReconnect) {
                $this->connections[$key] = null;
            }
        }

        return $this->connections[$key] ??= match (parse_url($dsn, PHP_URL_SCHEME)) {
            'redis', 'rediss' => RedisAdapter::createConnection($dsn, $options),
            'memcached' => MemcachedAdapter::createConnection($dsn, $options),
            default => throw new \InvalidArgumentException("Unsupported DSN scheme: " . parse_url($dsn, PHP_URL_SCHEME))
        };
    }

    public function getAdapter(string $key): AbstractAdapter
    {
        $namespace = $this->config[$key]['namespace'] ?? '';
        if (isset($this->config[$key]['adapter']['dsn'])) {
            $namespace = $this->connect($key);
        }

        return new $this->config[$key]['adapter']['class'](
            $namespace,
            $this->config[$key]['lifetime'] ?? 0,
            $this->config[$key]['directory'] ?? ''
        );
    }

    public function getCache(string $key): AbstractAdapter
    {
        if (!isset($this->caches[$key])) {
            $this->caches[$key] = $this->getAdapter($key);
        }
        return $this->caches[$key];
    }

    public function onCallback(): bool
    {
        $this->initConnections();
        return true;
    }
}
