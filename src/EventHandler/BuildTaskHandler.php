<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\TimeHelper;
use Symfony\Component\EventDispatcher\GenericEvent;

class BuildTaskHandler extends AbstractTaskEventHandler
{
    use \DigipolisGent\Robo\Task\Package\Tasks;
    use \DigipolisGent\Robo\Task\Package\Utility\NpmFindExecutable;
    use \Robo\Task\Base\Tasks;

    public function getPriority(): int {
      return parent::getPriority() - 100;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $event->stopPropagation();
        $archiveName = $event->hasArgument('archiveName') ? $event->getArgument('archiveName') : null;
        $archive = is_null($archiveName) ? TimeHelper::getInstance()->getTime() . '.tar.gz' : $archiveName;

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
