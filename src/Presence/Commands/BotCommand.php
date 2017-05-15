<?php

namespace Presence\Commands;

use PhpSlackBot\Bot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setHelp('Allows lalala')
            ->addArgument(
                'interface',
                InputArgument::OPTIONAL,
                'The interface name.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bot = new Bot();
        $bot->setToken(
            getenv('BOT_TOKEN')
        );
        $bot->loadCommand(new \Presence\Bot($input->getArgument('interface')));
        $bot->run();
    }
}
