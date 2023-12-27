<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;

abstract class SymfonyHandler extends AbstractTaskEventHandler
{
    use \DigipolisGent\Robo\Symfony\Util\SymfonyUtil;
}
