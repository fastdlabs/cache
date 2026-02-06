<?php

declare(strict_types=1);

namespace FastD\Cache\Listener;

use FastD\Event\BootedEvent;
use FastD\Event\EventListener;

class BootedEventListener extends EventListener
{
    public function process(object $event): void
    {
        if (container()->getRuntime() == 'swoole') {
            container()->got('cache')->initConnections();
        }
    }

    public function listen(): iterable
    {
        return [
            BootedEvent::class,
        ];
    }
}