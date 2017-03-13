# Robo Digipolis Symfony

Used by digipolis, serving as an example.

This package contains a RoboFileBase class that can be used in your own
RoboFile. All commands can be overwritten by overwriting the parent method.

## Example

```php
<?php

use DigipolisGent\Robo\Symfony\RoboFileBase;

class RoboFile extends RoboFileBase
{
    use \Robo\Task\Base\loadTasks;

    /**
     * @inheritdoc
     */
    public function digipolisDeploySymfony(
        array $arguments,
        $opts = [
            'app' => 'default',
            'worker' => null,
        ]
    ) {
        $collection = parent::digipolisDeploySymfony($arguments, $opts);
        $collection->taskExec('/usr/bin/custom-post-release-script.sh');
        return $collection;
    }
}

```

## Available commands

Following the example above, these commands will be available:

```bash
digipolis:backup-symfony           Create a backup of files (storage folder) and database.
digipolis:build-symfony            Build a Symfony site and package it.
digipolis:clean-dir                Partially clean directories.
digipolis:clear-op-cache           Command digipolis:database-backup.
digipolis:database-backup          Command digipolis:database-backup.
digipolis:database-restore         Command digipolis:database-restore.
digipolis:deploy-symfony           Build a Symfony site and push it to the servers.
digipolis:download-backup-symfony  Download a backup of files (storage folder) and database.
digipolis:init-symfony-remote      Install or update a Symfony remote site.
digipolis:install-symfony          Install the Symfony site in the current folder.
digipolis:package-project
digipolis:push-package             Command digipolis:push-package.
digipolis:restore-backup-symfony   Restore a backup of files (storage folder) and database.
digipolis:switch-previous          Switch the current release symlink to the previous release.
digipolis:sync-symfony             Sync the database and files between two Symfony sites.
digipolis:theme-clean
digipolis:theme-compile
digipolis:update-symfony           Executes database updates of the Symfony site in the current folder.
digipolis:upload-backup-symfony    Upload a backup of files (storage folder) and database to a server.
```
