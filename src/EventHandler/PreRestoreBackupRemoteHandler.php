<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler\PreRestoreBackupRemoteHandler as PreRestoreBackupRemoteHandlerBase;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Symfony\Component\EventDispatcher\GenericEvent;

class PreRestoreBackupRemoteHandler extends PreRestoreBackupRemoteHandlerBase implements CustomEventAwareInterface
{
    use \DigipolisGent\Robo\Symfony\Util\SymfonyUtil;
    use \DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
    use \Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;

    public function getPriority(): int
    {
        return parent::getPriority() + 100;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $options = $event->getArgument('options');
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());

        if (!$options['files'] && !$options['data']) {
            $options['files'] = true;
            $options['data'] = true;
        }

        $collection = $this->collectionBuilder();

        if ($options['data']) {
            $collection
                ->taskSsh($remoteConfig->getHost(), $auth)
                    ->remoteDirectory($remoteConfig->getCurrentProjectRoot(), true)
                    ->timeout(60)
                    ->exec($this->getConsolePath() . ' doctrine:schema:drop --full-database --force');
        }
        return $collection;
    }
}
