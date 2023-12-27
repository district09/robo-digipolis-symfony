<?php

namespace DigipolisGent\Robo\Symfony\Util;

trait SymfonyUtil
{

    protected function getConsolePath() {
        $paths = $this->handleEvent(
            'digipolis:symfony-console-path',
        );

        return reset($paths);
    }
}
