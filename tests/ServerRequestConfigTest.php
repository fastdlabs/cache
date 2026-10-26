<?php

declare(strict_types=1);

use FastD\Application;
use FastD\Cache\Middleware\XMCache;
use FastD\Cache\ServiceProvider\ServerRequestCacheProvider;
use FastD\Config\FileParser;
use FastD\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ConfigTestMatcher
{
    public array $pushed = [];

    public function push(MiddlewareInterface $middleware): void
    {
        $this->pushed[] = $middleware;
    }
}

final class ServerRequestConfigTest extends TestCase
{
    public function testProviderUsesHttpCacheConfig(): void
    {
        $matcher = new ConfigTestMatcher();
        $container = new class extends Container {
            public function config(string $key): array
            {
                return ['httpCache' => ['enable' => true, 'lifetime' => 60]];
            }
        };
        $container->add('matcher', $matcher);

        (new ServerRequestCacheProvider())->register($container);

        $this->assertCount(1, $matcher->pushed);
        $this->assertInstanceOf(XMCache::class, $matcher->pushed[0]);
    }

    public function testProviderFallsBackToLegacyXmCacheConfig(): void
    {
        $matcher = new ConfigTestMatcher();
        $container = new class extends Container {
            public function config(string $key): array
            {
                return ['xmCache' => ['enable' => true, 'lifetime' => 60]];
            }
        };
        $container->add('matcher', $matcher);

        (new ServerRequestCacheProvider())->register($container);

        $this->assertCount(1, $matcher->pushed);
    }

    public function testXMCachePrefersHttpCacheKey(): void
    {
        $root = sys_get_temp_dir() . '/fastd-cache-config-' . bin2hex(random_bytes(4));
        mkdir($root . '/config', 0777, true);
        $file = $root . '/config/cache.php';
        file_put_contents($file, "<?php return ['httpCache' => ['cache_keys' => ['a']], 'xmCache' => ['cache_keys' => ['b']]];");

        $app = new Application([
            'name' => 'test',
            'root' => $root,
            'timezone' => 'UTC',
            'runtime' => 'testcase',
        ]);
        $app->add('config', new FileParser());
        config()->parse($file);

        $middleware = new XMCache();
        $method = new ReflectionMethod(XMCache::class, 'config');
        $method->setAccessible(true);

        $this->assertSame(['cache_keys' => ['a']], $method->invoke($middleware));

        unlink($file);
        rmdir($root . '/config');
        rmdir($root);
    }

    public function testXMCacheFallsBackToXmCacheKey(): void
    {
        $root = sys_get_temp_dir() . '/fastd-cache-config-' . bin2hex(random_bytes(4));
        mkdir($root . '/config', 0777, true);
        $file = $root . '/config/cache.php';
        file_put_contents($file, "<?php return ['xmCache' => ['cache_keys' => ['legacy']]];");

        $app = new Application([
            'name' => 'test',
            'root' => $root,
            'timezone' => 'UTC',
            'runtime' => 'testcase',
        ]);
        $app->add('config', new FileParser());
        config()->parse($file);

        $middleware = new XMCache();
        $method = new ReflectionMethod(XMCache::class, 'config');
        $method->setAccessible(true);

        $this->assertSame(['cache_keys' => ['legacy']], $method->invoke($middleware));

        unlink($file);
        rmdir($root . '/config');
        rmdir($root);
    }
}
