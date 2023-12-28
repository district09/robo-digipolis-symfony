<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;

abstract class SymfonyHandler extends AbstractTaskEventHandler implements CustomEventAwareInterface
{
    use \DigipolisGent\Robo\Symfony\Util\SymfonyUtil;
    use \DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
    use \Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
}
