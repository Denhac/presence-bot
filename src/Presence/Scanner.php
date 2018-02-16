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
        
        $command = 'arp scan';

        // if no hosts specified, don't do the thing
        // just check for interface, and set --localnet
        if (is_null($this->hosts)) {
            $command .= $this->interface ? sprintf(
                ' --interface=%s --localnet',
                $this->interface
            ) : ' -l';
        // else, hosts are specified, and we don't need
        // --localnet, but still check interface
        else {
            $command .= $this->interface ? sprintf(
                ' --interface=%s %s',
                $this->interface, $this->hosts
            ) : sprintf(' %s', $this->hosts);
        }

        // If no interface is provided we will scan all.
        $command .= $this->interface ?: '-l ';
        // If no 

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
