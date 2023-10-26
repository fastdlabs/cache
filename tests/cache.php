<?php

 return [
     'file' => [
         'adapter' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
     ],
     'redis' => [
         'adapter' => \Symfony\Component\Cache\Adapter\RedisAdapter::class,
         'params' => [
             'dsn' => 'redis://127.0.0.1:3306/0',
         ]
     ],
 ];
