<?php

namespace DigipolisGent\Robo\Symfony\Util\TaskFactory;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Build as BuildBase;

class Build extends BuildBase
{
    use \DigipolisGent\Robo\Task\Package\Tasks;
    use \DigipolisGent\Robo\Task\Package\Utility\NpmFindExecutable;

    /**
     * Build a site and package it.
     *
     * @param string $archivename
     *   Name of the archive to create.
     *
     * @return \Robo\Contract\TaskInterface
     *   The deploy task.
     */
    public function buildTask($archivename = null)
    {
        $archive = is_null($archivename) ? $this->remoteHelper->getTime() . '.tar.gz' : $archivename;
        $collection = $this->collectionBuilder();
        if (file_exists('package.json')) {
            $collection
                ->taskThemeCompile()
                ->taskExec($this->findExecutable('yarn') . ' run encore production')
                ->taskThemeClean();
        }
        $collection->taskExec((string) CommandBuilder::create('rm')
            ->addFlag('rf')
            ->addRawArgument('var/cache/*')
        );
        $collection->taskPackageProject($archive)
            ->ignoreFileNames([
                '.gitattributes',
                '.gitignore',
                '.gitkeep',
                'README',
                'README.txt',
                'README.md',
                'LICENSE',
                'LICENSE.txt',
                'LICENSE.md',
                'phpunit.xml.dist',
                '.env.local',
            ]);
        return $collection;
    }
}
