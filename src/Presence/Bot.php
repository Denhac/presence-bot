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

use PhpSlackBot\Command\BaseCommand;

/**
 * Class Bot.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence
 */
class Bot extends BaseCommand
{
    protected function configure()
    {
        $this->setName('presence');
    }

    protected function execute($data, $context)
    {
        $this->arpScan();
    }

    protected function arpScan()
    {
        $scanner = new Scanner('en0');
        $records = $scanner->scan();
        $messages = array_map(
            function (MacRecord $record) {
                return sprintf(
                    "Found device with mac address `%s` (%s) and ip `%s`",
                    $record->mac,
                    $record->description,
                    $record->ip
                );
            },
            $records
        );
        if (count($messages)) {
            $this->send(
                $this->getCurrentChannel(),
                null,
                implode("\n", $messages)
            );
        }
    }
}
