<?php

namespace Presence;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Mac.
 *
 * @property int $id
 * @property string $user
 * @property string $description
 * @property int $minutes
 * @property Carbon $last_seen_at
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence
 *
 * @method static \Illuminate\Database\Eloquent\Builder active() get active mac addresses
 */
class Mac extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'macs';

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $guarded = [];

    /**
     * A human readable representation of the minutes spent in the office.
     *
     * @return string
     */
    public function getMinutesAsString()
    {
        $string = '';
        $days = floor($this->minutes / (60 * 24));
        $hours = floor($this->minutes / 60) % 24;
        $minutes = ($this->minutes % 60);
        if ($days) {
            $string .= $days . ' days, ';
        }
        if ($hours) {
            $string .= $hours . ' hours, ';
        }
        $string .= $minutes . ' minutes';

        return $string;
    }

    /**
     * Scope a query to only include active mac addresses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('last_seen_at', '<', Carbon::now()->subMinutes(3)->toDateTimeString());
    }
}
