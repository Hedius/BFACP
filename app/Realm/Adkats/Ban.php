<?php

namespace BFACP\Realm\Adkats;

use BFACP\Realm\Player;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Ban
 * @package BFACP\Realm\Adkats
 */
class Ban extends Model
{
    /**
     * Should model handle timestamps.
     *
     * @var bool
     */
    public $timestamps = false;
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'adkats_bans';
    /**
     * Table primary key.
     *
     * @var string
     */
    protected $primaryKey = 'ban_id';
    /**
     * Fields not allowed to be mass assigned.
     *
     * @var array
     */
    protected $guarded = ['ban_id', 'ban_sync'];
    /**
     * Date fields to convert to carbon instances.
     *
     * @var array
     */
    protected $dates = ['ban_startTime', 'ban_endTime'];
    /**
     * Append custom attributes to output.
     *
     * @var array
     */
    protected $appends = [
        'is_active',
        'is_expired',
        'is_unbanned',
        'is_perm',
        'ban_enforceName',
        'ban_enforceGUID',
        'ban_enforceIP',
        'ban_issued',
        'ban_expires',
    ];
    /**
     * Models to be loaded automatically.
     *
     * @var array
     */
    protected $with = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record()
    {
        return $this->belongsTo(Record::class, 'latest_record_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    /**
     * Gets the latest bans that are in effect.
     *
     * @param object $query
     * @param int    $limit
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function scopeLatest($query, $limit = 60)
    {
        return $query->where('ban_status', 'Active')->orderBy('ban_startTime', 'desc')->take($limit);
    }

    /**
     * Gets the bans done yesterday (UTC).
     *
     * @param object $query
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function scopeYesterday($query)
    {
        return $query->where('ban_startTime', '>=', Carbon::yesterday())->where('ban_startTime', '<=', Carbon::today());
    }

    /**
     * Get the bans that the player ids have done.
     *
     * @param object $query
     * @param array  $playerIds
     * @param int    $limit
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function scopePersonal($query, $playerIds = [], $limit = 30)
    {
        if (empty($playerIds)) {
            return $this;
        }

        return $query->join('adkats_records_main', 'adkats_bans.latest_record_id', '=',
            'adkats_records_main.record_id')->whereIn('adkats_records_main.source_id',
            $playerIds)->orderBy('ban_startTime', 'desc')->take($limit);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function previous()
    {
        return $this->hasMany(Record::class, 'target_id', 'player_id')->whereIn('command_action',
            [7, 8, 72, 73])->orderBy('record_time', 'desc');
    }

    /**
     * @return mixed
     */
    public function getBanIssuedAttribute()
    {
        return $this->ban_startTime->toIso8601String();
    }

    /**
     * @return mixed
     */
    public function getBanExpiresAttribute()
    {
        return $this->ban_endTime->toIso8601String();
    }

    /**
     * Is ban enforced by name.
     *
     * @return bool
     */
    public function getBanEnforceNameAttribute()
    {
        return $this->attributes['ban_enforceName'] == 'Y';
    }

    /**
     * IS ban enforced by guid.
     *
     * @return bool
     */
    public function getBanEnforceGUIDAttribute()
    {
        return $this->attributes['ban_enforceGUID'] == 'Y';
    }

    /**
     * Is ban enforced by ip.
     *
     * @return bool
     */
    public function getBanEnforceIPAttribute()
    {
        return $this->attributes['ban_enforceIP'] == 'Y';
    }

    /**
     * Is ban active.
     *
     * @return bool
     */
    public function getIsActiveAttribute()
    {
        return $this->attributes['ban_status'] == 'Active';
    }

    /**
     * Is ban expired.
     *
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        return $this->attributes['ban_status'] == 'Expired';
    }

    /**
     * Is unbanned.
     *
     * @return bool
     */
    public function getIsUnbannedAttribute()
    {
        return $this->attributes['ban_status'] == 'Disabled' || $this->attributes['ban_status'] == 'Expired';
    }

    /**
     * Is ban permanent.
     *
     * @return bool
     */
    public function getIsPermAttribute()
    {
        return $this->record->command_action == 8;
    }
}
