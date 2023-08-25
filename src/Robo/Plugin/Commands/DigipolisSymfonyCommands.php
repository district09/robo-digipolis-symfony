<?php

namespace DigipolisGent\Robo\Symfony\Robo\Plugin\Commands;

use DigipolisGent\Robo\Helpers\DependencyInjection\DeployTaskFactoryAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\SyncTaskFactoryAwareInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\DeployTaskFactoryAware;
use DigipolisGent\Robo\Helpers\DependencyInjection\Traits\SyncTaskFactoryAware;
use DigipolisGent\Robo\Helpers\Robo\Plugin\Commands\DigipolisHelpersCommands;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Backup as HelpersBackup;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Build as HelpersBuild;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Deploy;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\Sync;
use DigipolisGent\Robo\Symfony\Util\TaskFactory\Backup;
use DigipolisGent\Robo\Symfony\Util\TaskFactory\Build;
use DigipolisGent\Robo\Symfony\Util\TaskFactory\Symfony;
use League\Container\ContainerAwareInterface;
use League\Container\DefinitionContainerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;

class DigipolisSymfonyCommands extends DigipolisHelpersCommands implements
    DeployTaskFactoryAwareInterface,
    SyncTaskFactoryAwareInterface
{
    use DeployTaskFactoryAware;
    use SyncTaskFactoryAware;

    protected $console = 'bin/console';

    public function setContainer(DefinitionContainerInterface $container): ContainerAwareInterface
    {
        parent::setContainer($container);

        $container->extend(HelpersBackup::class)->setConcrete([Backup::class, 'create']);
        $container->extend(HelpersBuild::class)->setConcrete([Build::class, 'create']);

        $this->setDeployTaskFactory($container->get(Deploy::class));
        $this->setSyncTaskFactory($container->get(Sync::class));
        $this->setBackupTaskFactory($container->getNew(HelpersBackup::class));

        return $this;
    }

    public function getAppTaskFactoryClass()
    {
        return Symfony::class;
    }

    /**
     * Build a  site and push it to the servers.
     *
     * @param array $arguments
     *   Variable amount of arguments. The last argument is the path to the
     *   the private key file (ssh), the penultimate is the ssh user. All
     *   arguments before that are server IP's to deploy to.
     * @param array $opts
     *   The options for this command.
     *
     * @option app The name of the app we're deploying. Used to determine the
     *   directory to deploy to.
     * @option worker The IP of the worker server. Defaults to the first server
     *   given in the arguments.
     *
     * @usage --app=myapp 10.25.2.178 sshuser /home/myuser/.ssh/id_rsa
     */
    public function digipolisDeploySymfony(
        array $arguments,
        $opts = [
            'app' => 'default',
            'worker' => null,
        ]
    ) {
        return $this->deployTaskFactory->deployTask($arguments, $opts);
    }

    /**
     * Install the Symfony site in the current folder.
     */
    public function digipolisInstallSymfony()
    {
        $collection = $this->collectionBuilder();
        $collection
            ->taskExecStack()
                ->exec($this->console . ' cache:clear')
                ->exec($this->console . ' doctrine:migrations:migrate --no-interaction');
        return $collection;
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
        $collection = $this->collectionBuilder();
        $collection
            ->taskExecStack()
                ->exec($this->console . ' cache:clear')
                ->exec($this->console . ' doctrine:migrations:migrate --no-interaction');
        return $collection;
    }

    /**
     * Sync the database and files between two sites.
     *
     * @param string $sourceUser
     *   SSH user to connect to the source server.
     * @param string $sourceHost
     *   IP address of the source server.
     * @param string $sourceKeyFile
     *   Private key file to use to connect to the source server.
     * @param string $destinationUser
     *   SSH user to connect to the destination server.
     * @param string $destinationHost
     *   IP address of the destination server.
     * @param string $destinationKeyFile
     *   Private key file to use to connect to the destination server.
     * @param string $sourceApp
     *   The name of the source app we're syncing. Used to determine the
     *   directory to sync.
     * @param string $destinationApp
     *   The name of the destination app we're syncing. Used to determine the
     *   directory to sync to.
     */
    public function digipolisSyncSymfony(
        $sourceUser,
        $sourceHost,
        $sourceKeyFile,
        $destinationUser,
        $destinationHost,
        $destinationKeyFile,
        $sourceApp = 'default',
        $destinationApp = 'default',
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }
        return $this->syncTaskFactory->syncTask(
            $sourceUser,
            $sourceHost,
            $sourceKeyFile,
            $destinationUser,
            $destinationHost,
            $destinationKeyFile,
            $sourceApp,
            $destinationApp,
            $opts
        );
    }

    /**
     * @hook on-event digipolis-db-config
     */
    public function defaultDbConfig()
    {
        $rootDir = $this->getConfig()->get('digipolis.root.project', false);
        if (!$rootDir) {
            return false;
        }

        $finder = new Finder();
        $finder->in($rootDir)->ignoreDotFiles(false)->files()->name('.env');
        foreach ($finder as $settingsFile) {
            $env = new Dotenv();
            $env->loadEnv(dirname($settingsFile->getRealPath()) . DIRECTORY_SEPARATOR . $settingsFile->getFilename());
            break;
        }

        $url = $this->env('DATABASE_URL', 'mysql://symfony:symfony@localhost:3306/symfony');
        $matches = [];
        preg_match('/^([^:\/\/]*):\/\/([^:]*):([^@]*)@([^:]*):([^\/]*)\/([^\?]*)(\?serverVersion=.*)?$/', $url, $matches);
        return [
          'default' => [
                'type' => $matches[1],
                'user' => $matches[2],
                'pass' => $matches[3],
                'host' => $matches[4],
                'port' => $matches[5],
                'database' => $matches[6],
                'structureTables' => [],
                'extra' => '--skip-add-locks --no-tablespaces',
            ]
        ];
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    protected function env($key, $default = null)
    {
        $value = array_key_exists($key, $_ENV) ? $_ENV[$key] : false;
        if ($value === false) {
            return is_callable($default) ? call_user_func($default) : $default;
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        if (strlen($value) > 1 && substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
            return substr($value, 1, -1);
        }
        return $value;
    }

}
