<?php

/**
 * Copyright 2014-2016, SellerLabs <snagshout-devs@sellerlabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the Snagshout package
 */

namespace Presence\Commands;

use PhpSlackBot\Bot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BotCommand.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence\Commands
 */
class BotCommand extends Command
{
    protected function configure()
    {
        $this->setName('bot')
            ->setDescription('Launch the bot')
            ->setHelp('Allows lalala');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bot = new Bot();
        $bot->setToken(
            getenv('BOT_TOKEN') || 'xoxb-181546698032-GKAERfrwWK3plcbCZq0wWAZf'
        );
        $bot->loadCommand(new \Presence\Bot());
        $bot->run();
    }
}
