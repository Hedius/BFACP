<?php

namespace BFACP\Realm\Player;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Session
 * @package BFACP\Realm\Player
 */
class Session extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'tbl_sessions';

    /**
     * @var string
     */
    protected $primaryKey = 'StatsID';

    /**
     * @var array
     */
    protected $guarded = ['*'];

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @var array
     */
    protected $dates = ['StartTime', 'EndTime'];

    /**
     * @var array
     */
    protected $appends = ['session_start', 'session_end'];

    /**
     * @return mixed
     */
    public function getFirstSeenAttribute()
    {
        return $this->StartTime->toIso8601String();
    }

    /**
     * @return mixed
     */
    public function getLastSeenAttribute()
    {
        return $this->EndTime->toIso8601String();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function server()
    {
        return $this->belongsToMany(\BFACP\Realm\Server::class, 'tbl_server_player', 'StatsID', 'ServerID');
    }
}
