<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace FastD\CacheProvider;


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
     */
    public function register(Container $container): void
    {
        $config = $container->get('config')->get('cache', []);

        $container->add('cache', new CachePool($config));

        unset($config);
    }
}
