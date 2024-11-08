<?php

namespace DigipolisGent\Robo\Symfony\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use DigipolisGent\Robo\Symfony\EventHandler\BuildTaskHandler;
use DigipolisGent\Robo\Symfony\EventHandler\ClearCacheHandler;
use DigipolisGent\Robo\Symfony\EventHandler\InstallHandler;
use DigipolisGent\Robo\Symfony\EventHandler\InstallSymfonyHandler;
use DigipolisGent\Robo\Symfony\EventHandler\IsSiteInstalledHandler;
use DigipolisGent\Robo\Symfony\EventHandler\PreRestoreBackupRemoteHandler;
use DigipolisGent\Robo\Symfony\EventHandler\SymfonyConsolePathHandler;
use DigipolisGent\Robo\Symfony\EventHandler\UpdateHandler;
use DigipolisGent\Robo\Symfony\EventHandler\UpdateSymfonyHandler;
use Robo\Contract\ConfigAwareInterface;
use Robo\Tasks;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;

class DigipolisSymfonyDefaultHooksCommands extends Tasks implements ConfigAwareInterface, CustomEventAwareInterface
{

    use \Consolidation\Config\ConfigAwareTrait;
    use \DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAware;
    use \DigipolisGent\Robo\Task\General\Tasks;
    use \DigipolisGent\Robo\Helpers\Traits\EventDispatcher;
    use \Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
    use \DigipolisGent\Robo\Helpers\Traits\DigipolisHelpersCommandUtilities;

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
        $finder->in($rootDir)->exclude('vendor')->ignoreDotFiles(false)->files()->name('.env');
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

    /**
     * @hook on-event digipolis:install-symfony
     */
    public function getInstallSymfonyHandler()
    {
        return new InstallSymfonyHandler();
    }

    /**
     * @hook on-event digipolis:update-symfony
     */
    public function getUpdateSymfonyHandler()
    {
        return new UpdateSymfonyHandler();
    }

    /**
     * @hook on-event digipolis:pre-restore-backup-remote
     */
    public function getPreRestoreBackupRemoteHandler() {
        return new PreRestoreBackupRemoteHandler();
    }

    /**
     * @hook on-event digipolis:build-task
     */
    public function getBuildTaskHandler()
    {
        return new BuildTaskHandler();
    }

    /**
     * @hook on-event digipolis:install
     */
    public function getInstallHandler()
    {
        return new InstallHandler();
    }

    /**
     * @hook on-event digipolis:update
     */
    public function getUpdateHandler()
    {
        return new UpdateHandler();
    }

    /**
     * @hook on-event digipolis:clear-cache
     */
    public function getClearCacheHandler()
    {
        return new ClearCacheHandler();
    }

    /**
     * @hook on-event digipolis:symfony-console-path
     */
    public function getSymfonyConsolePathHandler()
    {
        return new SymfonyConsolePathHandler();
    }

    /**
     * @hook on-event digipolis:is-site-installed
     */
    public function getIsSiteInstalledHandler()
    {
        return new IsSiteInstalledHandler();
    }
}
