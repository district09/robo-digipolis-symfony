<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class ClearCacheHandler extends SymfonyHandler
{
    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $currentWebRoot = $remoteSettings['currentdir'];
        $console = $this->getConsolePath();
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        return $this->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($currentWebRoot . '/..', true)
            ->timeout(120)
            ->exec($console . ' cache:clear')
            ->exec($console . ' cache:warmup');
    }
}
