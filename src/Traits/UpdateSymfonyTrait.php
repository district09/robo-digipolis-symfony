<?php

namespace DigipolisGent\Robo\Symfony\Traits;

use DigipolisGent\Robo\Helpers\Traits\AbstractDeployCommandTrait;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

trait UpdateSymfonyTrait
{
    /**
     * @see \DigipolisGent\Robo\Helpers\Traits\TraitDependencyCheckerTrait
     */
    protected function getUpdateSymfonyTraitDependencies()
    {
        return [AbstractDeployCommandTrait::class];
    }

    protected function updateTask($server, AbstractAuth $auth, $remote, $extra = [])
    {
        $currentProjectRoot = $remote['rootdir'];
        return $this->taskSsh($server, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            // Updates can take a long time. Let's set it to 30 minutes.
            ->timeout(1800)
            ->exec('vendor/bin/robo digipolis:update-symfony');
    }

    /**
     * Executes database updates of the Symfony site in the current folder.
     *
     * Executes database updates of the Symfony site in the current folder. Sets
     * the site in maintenance mode before the update and takes in out of
     * maintenance mode after.
     */
    public function digipolisUpdateSymfony()
    {
        $this->readProperties();
        $collection = $this->collectionBuilder();
        $collection
            ->taskExecStack()
                ->exec($this->console . ' cache:clear')
                ->exec($this->console . ' doctrine:migrations:migrate --no-interaction');
        return $collection;
    }
}
