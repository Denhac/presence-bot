<?php

namespace Presence\Commands;

use Carbon\Carbon;
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

                return;
            }
            $mac->last_seen_at = Carbon::now()->toDateTimeString();
            $mac->minutes++;
            $mac->save();
        }
    }
}
