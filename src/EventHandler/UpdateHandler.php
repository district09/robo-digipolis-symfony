<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class UpdateHandler extends SymfonyHandler
{
    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    public function getPriority(): int
    {
        return parent::getPriority() - 100;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $currentProjectRoot = $remoteSettings['currentdir'] . '/..';
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        return $this->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($currentProjectRoot, true)
            // Updates can take a long time. Let's set it to 30 minutes.
            ->timeout(1800)
            ->exec('vendor/bin/robo digipolis:update-symfony');
    }
}
