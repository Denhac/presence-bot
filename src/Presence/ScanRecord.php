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
            "Found device with mac address `%s` (%s) and ip `%s`",
            $this->mac,
            $this->description,
            $this->ip
        );
    }
}
