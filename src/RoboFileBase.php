<?php

namespace DigipolisGent\Robo\Symfony;

use DigipolisGent\Robo\Helpers\AbstractRoboFile;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;

class RoboFileBase extends AbstractRoboFile
{
    use \DigipolisGent\Robo\Task\Package\Utility\NpmFindExecutable;
    use \DigipolisGent\Robo\Task\CodeValidation\loadTasks;
    use \DigipolisGent\Robo\Helpers\Traits\AbstractCommandTrait;
    use \DigipolisGent\Robo\Task\Deploy\Commands\loadCommands;
    use \DigipolisGent\Robo\Task\Package\Traits\ThemeCompileTrait;
    use \DigipolisGent\Robo\Task\Package\Traits\ThemeCleanTrait;
    use Traits\BuildSymfonyTrait;
    use Traits\DeploySymfonyTrait;
    use Traits\UpdateSymfonyTrait;
    use Traits\InstallSymfonyTrait;
    use Traits\SyncSymfonyTrait;

    /**
     * Path to the symfony console executable.
     */
    protected $console;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->console = 'bin/console';
    }

    /**
     * {@inheritdoc}
     */
    protected function isSiteInstalled($worker, AbstractAuth $auth, $remote)
    {
        $currentProjectRoot = $this->getCurrentProjectRoot($worker, $auth, $remote);
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

    public function digipolisValidateCode()
    {
        $local = $this->getLocalSettings();
        $directories = [
            $local['project_root'] . '/src',
        ];

        // Check if directories exist.
        $checks = [];
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                continue;
            }

            $checks[] = $dir;
        }
        if (!$checks) {
            $this->say('! No custom directories to run checks on.');
            return;
        }
        $phpcs = $this
            ->taskPhpCs(
                implode(' ', $checks),
                'PSR1,PSR2',
                $phpcsExtensions
            )
            ->ignore([
                'node_modules',
                'Gruntfile.js',
                '*.md',
                '*.min.js',
                '*.css'
            ])
            ->reportType('full');
        $phpmd = $this->taskPhpMd(
            implode(',', $checks),
            'text',
            $phpmdExtensions
        );
        $collection = $this->collectionBuilder();
        // Add the PHPCS task to the rollback as well so we always have the full
        // report.
        $collection->rollback($phpcs);
        $collection->addTask($phpmd);
        $collection->addTask($phpcs);
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    protected function preRestoreBackupTask(
        $worker,
        AbstractAuth $auth,
        $remote,
        $opts = ['files' => false, 'data' => false]
    ) {
        if (!$opts['files'] && !$opts['data']) {
            $opts['files'] = true;
            $opts['data'] = true;
        }
        $currentProjectRoot = $this->getCurrentProjectRoot($worker, $auth, $remote);
        $collection = $this->collectionBuilder();
        $parent = parent::preRestoreBackupTask($worker, $auth, $remote, $opts);
        if ($parent) {
            $collection->addTask($parent);
        }

        if ($opts['data']) {
            $collection
                ->taskSsh($worker, $auth)
                    ->remoteDirectory($currentProjectRoot, true)
                    ->timeout(60)
                    ->exec($this->console . ' doctrine:schema:drop --full-database --force');
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    protected function clearCacheTask($worker, $auth, $remote)
    {
        $currentProjectRoot = $remote['rootdir'];
        return $this->taskSsh($worker, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            ->timeout(120)
            ->exec($this->console . ' cache:clear')
            ->exec($this->console . ' cache:warmup');
    }

    /**
     * {@inheritdoc}
     */
    protected function buildTask($archivename = null)
    {
        $archive = is_null($archivename) ? $this->time . '.tar.gz' : $archivename;
        $collection = $this->collectionBuilder();
        $collection
            ->taskThemeCompile()
            ->taskExec($this->findExecutable('yarn') . ' run encore production')
            ->taskThemeClean()
            ->taskPackageProject($archive)
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
                    'phpunit.xml.dist'
                ]);
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDbConfig()
    {
        $rootDir = $this->getConfig()->get('digipolis.root.project', false);
        if (!$rootDir) {
            return false;
        }

        $finder = new Finder();
        $finder->in($rootDir)->ignoreDotFiles(false)->files()->name('.env');
        foreach ($finder as $settingsFile) {
            $env = new Dotenv(dirname($settingsFile->getRealPath()), $settingsFile->getFilename());
            $env->load();
            break;
        }

        $url = $this->env('DATABASE_URL', 'mysql://symfony:symfony@localhost:3306/symfony');
        $matches = [];
        preg_match('/^([^:\/\/]*):\/\/([^:]*):([^@]*)@([^:]*):([^\/]*)\/(.*)$/', $url, $matches);
        return [
          'default' => [
                'type' => $matches[1],
                'user' => $matches[2],
                'pass' => $matches[3],
                'host' => $matches[4],
                'port' => $matches[5],
                'database' => $matches[6],
                'structureTables' => [],
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
        $value = getenv($key);
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
