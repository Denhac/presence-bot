<?php

namespace Presence;

use Illuminate\Database\Capsule\Manager;
use josegonzalez\Dotenv\Loader;
use LogicException;
use Presence\Commands\BotCommand;
use Presence\Commands\DatabaseCommand;
use Presence\Commands\ScanCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Class Application.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence
 */
class Application extends BaseApplication
{
    /**
     * Loads the .env file. checking weather the BOT_TOKEN is present.
     *
     * @param $path
     *
     * @throws PresenceException
     * @throws LogicException
     * @return $this
     */
    public function loadEnv($path)
    {
        $config = $path . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($path)) {
            throw new PresenceException('.env file does not exist!');
        }

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
     * Registers all possible commands for the command line.
     *
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
     * Connect to the database.
     *
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

    /**
     * Retrieve ENV values or return their default.
     *
     * @param string $env
     * @param null $default
     *
     * @return array|false|null|string
     */
    protected function conf($env, $default = null)
    {
        $value = getenv($env);

        return $value ? $value : $default;
    }
}
