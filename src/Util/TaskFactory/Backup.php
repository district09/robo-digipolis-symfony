<?php

namespace DigipolisGent\Robo\Symfony\Util\TaskFactory;

use DigipolisGent\Robo\Helpers\Util\TaskFactory\Backup as BackupBase;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

class Backup extends BackupBase
{
    /**
     * Pre restore backup task.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return bool|\Robo\Contract\TaskInterface
     *   The pre restore backup task, false if no pre restore backup tasks need
     *   to run.
     */
    protected function preRestoreBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }
        $currentProjectRoot = $this->getCurrentProjectRoot($worker, $auth, $remote);
        $collection = $this->collectionBuilder();
        $parent = parent::preRestoreBackupTask($worker, $auth, $remote, $opts);
        if ($parent) {
            $collection->addTask($parent);
        }

        if ($opts['data']) {
            $collection
                ->taskSsh($worker, $auth)
                    ->remoteDirectory($currentProjectRoot, true)
                    ->timeout(60)
                    ->exec($this->console . ' doctrine:schema:drop --full-database --force');
        }
        return $collection;
    }
}
