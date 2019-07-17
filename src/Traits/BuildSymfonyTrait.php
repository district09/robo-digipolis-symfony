<?php

namespace DigipolisGent\Robo\Symfony\Traits;

use DigipolisGent\Robo\Helpers\Traits\AbstractDeployCommandTrait;

trait BuildSymfonyTrait
{
    /**
     * @see \DigipolisGent\Robo\Helpers\Traits\TraitDependencyCheckerTrait
     */
    protected function getBuildSymfonyTraitDependencies()
    {
        return [AbstractDeployCommandTrait::class];
    }

    /**
     * Build a Symfony site and package it.
     *
     * @param string $archivename
     *   Name of the archive to create.
     *
     * @usage test.tar.gz
     */
    public function digipolisBuildSymfony($archivename = null)
    {
        return $this->buildTask($archivename);
    }
}
