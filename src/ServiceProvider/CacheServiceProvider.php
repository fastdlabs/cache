<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace FastD\Cache\ServiceProvider;


use Exception;
use FastD\Cache\CachePool;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;

/**
 * Class CacheServiceProvider
 * @package FastD\CacheProvider
 */
class CacheServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return mixed
     * @throws Exception
     */
    public function register(Container $container): void
    {
        $config = config()->replace(app()->getBootstrap('cache'));
        config()->add(['cache' => $config]);
        $container->add('cache', new CachePool($config));
        unset($config);
    }
}
