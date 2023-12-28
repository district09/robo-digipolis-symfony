<?php

namespace DigipolisGent\Robo\Symfony\EventHandler;

use Symfony\Component\EventDispatcher\GenericEvent;

class InstallSymfonyHandler extends SymfonyHandler
{
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
