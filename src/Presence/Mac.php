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

use Illuminate\Database\Eloquent\Model;

/**
 * Class Mac.
 *
 * @author Mark Vaughn <mark@roundsphere.com>
 * @package Presence
 */
class Mac extends Model
{
    protected $table = 'macs';
    public $timestamps = false;

    protected $guarded = [];
}
