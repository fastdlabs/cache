<?php

use Symfony\Component\Cache\Adapter\AbstractAdapter;

function cache(string $name): AbstractAdapter
{
    if (!function_exists('app')) {
        throw new Exception('Cache service provider is not register');
    }

    return app()->get('cache')->getCache($name);
}
