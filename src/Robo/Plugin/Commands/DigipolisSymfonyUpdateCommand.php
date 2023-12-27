<?php

namespace DigipolisGent\Robo\Symfony\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Tasks;

class DigipolisSymfonyUpdateCommand extends Tasks implements CustomEventAwareInterface, ConfigAwareInterface
{

    use \DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
    use \Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
    use \Consolidation\Config\ConfigAwareTrait;
    use \DigipolisGent\Robo\Symfony\Traits\AliasesHelper;
    use \DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAware;
    use \DigipolisGent\Robo\Task\General\Tasks;

    /**
     * Install the Symfony site in the current folder.
     *
     * @command digipolis:update-symfony
     */
    public function digipolisUpdateSymfony() {
        $this->readProperties();
        return $this->handleTaskEvent(
            'digipolis:update-symfony'
        );
    }
}
