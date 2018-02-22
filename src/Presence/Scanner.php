<?php

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
    public function __construct($interface = null, $hosts = null)
    {
        $this->interface = $interface;
        $this->hosts = $hosts;
    }

    /**
     * Runs arp scan and returns a list of Scan Records.
     *
     * @return \Illuminate\Support\Collection
     */
    public function scan()
    {
        
        $command = 'arp-scan';

        // test if we specified interface first
        if (isset($this->interface)) {
            $command = "{$command} --interface={$this->interfaces}";
        }
        
        // figure out if we set hosts, or are using --localnet
        if (isset($this->hosts)) {
            $command = "${command} {$this->hosts}";
        } else {
            $command = "${command} --localnet";
        }

        $arp_scan = shell_exec(
            $command
        );

        $arp_scan = explode("\n", $arp_scan);

        $records = collect();
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

            $records->push($record);
        }

        return $records;
    }
}
