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

/**
 * Class Scanner.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence
 */
class Scanner
{
    /**
     * @var string
     */
    private $interface;

    /**
     * Scanner constructor.
     *
     * @param string $interface
     */
    public function __construct($interface = null)
    {
        $this->interface = $interface;
    }

    /**
     * Runs arp scan and returns a list of Scan Records.
     *
     * @return ScanRecord[]
     */
    public function scan()
    {
        // If no interface is provided we will scan all.
        $command = $this->interface ? sprintf(
            'arp-scan --interface=%s -l',
            $this->interface
        ) : 'arp-scan -l';

        $arp_scan = shell_exec(
            $command

        );
        $arp_scan = explode("\n", $arp_scan);

        $matches = $records = [];
        foreach ($arp_scan as $scan) {
            $matches = [];

            // Matching the arp-scan output
            if (preg_match(
                    '/^([0-9\.]+)[[:space:]]+([0-9a-f:]+)[[:space:]]+(.+)$/',
                    $scan,
                    $matches
                ) !== 1
            ) {
                continue;
            }

            $record = new ScanRecord();
            $record->ip = $matches[1];
            $record->mac = $matches[2];
            $record->description = $matches[3];

            $records[] = $record;
        }

        return $records;
    }
}
