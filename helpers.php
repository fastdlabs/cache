<?php

use Symfony\Component\Cache\Adapter\AbstractAdapter;

function cache(string $name): AbstractAdapter
{
    if (!function_exists('container')) {
        throw new Exception('Cache service provider is not register');
    }

    return container()->get('cache')->getCache($name);
}
