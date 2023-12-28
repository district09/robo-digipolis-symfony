<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use Symfony\Component\EventDispatcher\GenericEvent;

class UpdateSymfonyHandler extends SymfonyHandler
{

    use \Robo\Task\Base\Tasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $collection = $this->collectionBuilder();

        $console = $this->getConsolePath();
        $collection
            ->taskExecStack()
                ->exec($console . ' cache:clear')
                ->exec($console . ' doctrine:migrations:migrate --no-interaction');
        return $collection;
    }
}
