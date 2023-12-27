<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class InstallHandler extends SymfonyHandler
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
        $force = $event->getArgument('force');
        $currentProjectRoot = $remoteSettings['currentdir'] . '/..';
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());

        $console = $this->getConsolePath();
        $collection = $this->collectionBuilder();
        $collection->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($currentProjectRoot, true)
            // Install can take a long time. Let's set it to 15 minutes.
            ->timeout(900);
        if ($force) {
            $collection->exec($console . ' doctrine:schema:drop --full-database --force');
        }
        $collection->exec('vendor/bin/robo digipolis:install-symfony');

        return $collection;
    }
}
