<?php

namespace Presence;

/**
 * Class ScanRecord.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence
 */
class ScanRecord
{
    public $ip;

    public $mac;

    public $description;

    public function __toString()
    {
        return sprintf(
            'Found device with mac address `%s` (%s) and ip `%s`',
            $this->mac,
            $this->description,
            $this->ip
        );
    }
}
