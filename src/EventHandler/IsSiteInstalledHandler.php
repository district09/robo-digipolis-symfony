<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class IsSiteInstalledHandler extends SymfonyHandler
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

        $console = $this->getConsolePath();
        $currentProjectRoot = $remoteConfig->getCurrentProjectRoot();
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        $migrateStatus = '';
        $status = $this->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($currentProjectRoot, true)
            ->exec($console . ' doctrine:migrations:status', function ($output) use ($migrateStatus) {
                $migrateStatus .= $output;
            })
            ->run()
            ->wasSuccessful();
        return $status && $migrateStatus != 'No migrations found.';
    }
}
