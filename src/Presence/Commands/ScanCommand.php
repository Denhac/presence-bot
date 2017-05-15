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

use Presence\Mac;
use Presence\Scanner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ScanCommand.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence\Commands
 */
class ScanCommand extends Command
{
    protected function configure()
    {
        $this->setName('scan')
            ->addArgument(
                'interface',
                InputArgument::OPTIONAL,
                'The interface name.'
            )
            ->setDescription('Launch the bot')
            ->setHelp('Allows lalala');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scanner = new Scanner($input->getArgument('interface'));
        $records = $scanner->scan();
        foreach ($records as $record) {
            $mac = Mac::find($record->mac);
            if ($mac == null) {
                Mac::create(
                    [
                        'id' => $record->mac,
                        'user' => 'unknown',
                        'description' => $record->description,
                        'minutes' => 1,
                    ]
                );
            } else {
                $mac->minutes++;
                $mac->save();
            }
        }
    }
}
