<?php

namespace DigipolisGent\Robo\Symfony\Util\TaskFactory;

use Consolidation\Config\ConfigInterface;
use DigipolisGent\Robo\Helpers\DependencyInjection\RemoteHelperAwareInterface;
use DigipolisGent\Robo\Helpers\Util\RemoteHelper;
use DigipolisGent\Robo\Helpers\Util\TaskFactory\AbstractApp;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use League\Container\DefinitionContainerInterface;
use Robo\Collection\CollectionBuilder;

class Symfony extends AbstractApp implements RemoteHelperAwareInterface
{
    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use \Robo\Task\Base\Tasks;
    use \DigipolisGent\Robo\Helpers\DependencyInjection\Traits\RemoteHelperAware;

    protected $siteInstalled = null;

    protected $console = 'bin/console';

    public function __construct(ConfigInterface $config, RemoteHelper $remoteHelper)
    {
        parent::__construct($config);
        $this->setRemoteHelper($remoteHelper);
    }

    public static function create(DefinitionContainerInterface $container)
    {
        $object = new static(
            $container->get('config'),
            $container->get(RemoteHelper::class)
        );
        $object->setBuilder(CollectionBuilder::create($container, $object));

        return $object;
    }

    /**
     * Install the site in the current folder.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     * @param bool $force
     *   Whether or not to force the install even when the site is present.
     *
     * @return \Robo\Contract\TaskInterface
     *   The install task.
     */
    public function installTask($worker, AbstractAuth $auth, $remote, $extra = [], $force = false)
    {
        $currentProjectRoot = $remote['rootdir'];
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
     * Executes database updates of the site in the current folder.
     *
     * Executes database updates of the site in the current folder. Sets
     * the site in maintenance mode before the update and takes in out of
     * maintenance mode after.
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return \Robo\Contract\TaskInterface
     *   The update task.
     */
    public function updateTask($worker, AbstractAuth $auth, $remote, $extra = [])
    {
        $currentProjectRoot = $remote['rootdir'];
        return $this->taskSsh($server, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            // Updates can take a long time. Let's set it to 30 minutes.
            ->timeout(1800)
            ->exec('vendor/bin/robo digipolis:update-symfony');
    }

    /**
     * Check if a site is already installed
     *
     * @param string $worker
     *   The server to install the site on.
     * @param AbstractAuth $auth
     *   The ssh authentication to connect to the server.
     * @param array $remote
     *   The remote settings for this server.
     *
     * @return bool
     *   Whether or not the site is installed.
     */
    public function isSiteInstalled($worker, AbstractAuth $auth, $remote)
    {
        $currentProjectRoot = $this->remoteHelper->getCurrentProjectRoot($worker, $auth, $remote);
        $migrateStatus = '';
        $status = $this->taskSsh($worker, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            ->exec($this->console . ' doctrine:migrations:status', function ($output) use ($migrateStatus) {
                $migrateStatus .= $output;
            })
            ->run()
            ->wasSuccessful();
        return $status && $migrateStatus != 'No migrations found.';
    }

    public function clearCacheTask($worker, $auth, $remote)
    {
        return $this->taskSsh($worker, $auth)
            ->remoteDirectory($remote['currentdir'] . '/..', true)
            ->timeout(120)
            ->exec($this->console . ' cache:clear')
            ->exec($this->console . ' cache:warmup');
    }

}
