<?php

/**
 * Copyright 2014-2016, SellerLabs <snagshout-devs@sellerlabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the Snagshout package
 */

namespace Presence;

use Illuminate\Database\Schema\Blueprint;
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

        //SUPER SECURE!
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
