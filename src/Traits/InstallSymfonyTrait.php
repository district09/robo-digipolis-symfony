<?php

namespace DigipolisGent\Robo\Symfony\Traits;

use DigipolisGent\Robo\Helpers\Traits\AbstractDeployCommandTrait;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;

trait InstallSymfonyTrait
{
    /**
     * @see \DigipolisGent\Robo\Helpers\Traits\TraitDependencyCheckerTrait
     */
    protected function getInstallSymfonyTraitDependencies()
    {
        return [AbstractDeployCommandTrait::class];
    }

    protected function installTask($worker, AbstractAuth $auth, $remote, $extra = [], $force = false)
    {
        $currentProjectRoot = $remote['rootdir'];;
        $collection = $this->collectionBuilder();
        $collection->taskSsh($worker, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            // Install can take a long time. Let's set it to 15 minutes.
            ->timeout(900);
        if ($force) {
            $collection->exec($this->console . ' doctrine:schema:drop --full-database --force');
        }
        $collection->exec('vendor/bin/robo digipolis:install-symfony');
        return $collection;
    }

    /**
     * Install the Symfony site in the current folder.
     */
    public function digipolisInstallSymfony()
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
