<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use Symfony\Component\EventDispatcher\GenericEvent;

class SymfonyConsolePathHandler extends AbstractTaskEventHandler
{
    public function handle(GenericEvent $event)
    {
        $event->stopPropagation();

        return 'bin/console';
    }
}
