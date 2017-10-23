<?php

namespace BFACP\Realm\Player;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Player
 * @package BFACP\Realm
 */
class Stat extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'tbl_playerstats';

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
    protected $dates = ['FirstSeenOnServer', 'LastSeenOnServer'];

    /**
     * @var array
     */
    protected $appends = ['first_seen', 'last_seen'];

    /**
     * @return mixed
     */
    public function getFirstSeenAttribute()
    {
        return $this->FirstSeenOnServer->toIso8601String();
    }

    /**
     * @return mixed
     */
    public function getLastSeenAttribute()
    {
        return $this->LastSeenOnServer->toIso8601String();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function server()
    {
        return $this->belongsToMany(\BFACP\Realm\Server::class, 'tbl_server_player', 'StatsID',
            'ServerID');
    }
}
