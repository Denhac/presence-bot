<?php

namespace Presence;

use josegonzalez\Dotenv\Loader;
use Presence\Commands\BotCommand;
use Presence\Commands\DatabaseCommand;
use Presence\Commands\ScanCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Illuminate\Database\Capsule\Manager;

/**
 * Class Application.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence
 */
class Application extends BaseApplication
{
    /**
     * @param string $path
     *
     * @return $this
     */
    public function loadEnv($path)
    {
        $config = $path . DIRECTORY_SEPARATOR . '.env';
        echo 'Loading config ' . $config . "\n";
        Loader::load(
            [
                'filepath' => $config,
                'expect' => ['BOT_TOKEN'],
                'toEnv' => true,
                'toServer' => true,
                'define' => true,
            ]
        )->putenv();

        return $this;
    }

    /**
     * @return $this
     */
    public function registerCommands()
    {
        $this->addCommands(
            [
                new DatabaseCommand(),
                new BotCommand(),
                new ScanCommand(),
            ]
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function bootDatabase()
    {
        $manager = new Manager();

        $manager->addConnection(
            [
                'driver' => 'mysql',
                'host' => $this->conf('DB_HOST', 'localhost'),
                'database' => $this->conf('DB_NAME', 'presence'),
                'username' => $this->conf('DB_USERNAME', 'root'),
                'password' => $this->conf('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
            ]
        );
        $manager->setAsGlobal();
        $manager->bootEloquent();

        return $this;
    }

    protected function conf($env, $default = null)
    {
        $value = getenv($env);

        return $value ? $value : $default;
    }
}
