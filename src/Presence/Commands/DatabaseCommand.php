<?php

namespace Presence\Commands;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DatabaseCommand.
 *
 * This command needs to be run to create the required tables for the software.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence\Commands
 */
class DatabaseCommand extends Command
{
    protected function configure()
    {
        $this->setName('database')
            ->setDescription('Set up the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Manager::schema()->create(
            'macs',
            function (Blueprint $table) {
                $table->char('id', 17)->primary();
                $table->string('user', 128)->nullable();
                $table->string('description', 128)->nullable();
                $table->integer('minutes')->default(0);
                $table->dateTime('last_seen_at')->nullable();
            }
        );
    }
}
