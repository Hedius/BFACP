<?php

namespace BFACP\Realm;

use BFACP\Realm\Adkats\Battlelog;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Player
 * @package BFACP\Realm
 * @property integer $PlayerID
 * @property integer $GameID
 * @property string  $ClanTag
 * @property string  $SoldierName
 * @property integer $GlobalRank
 * @property string  $PBGUID
 * @property string  $EAGUID
 * @property string  $IP_Address
 * @property string  $DiscordID
 * @property string  $IPv6_Address
 * @property string  $CountryCode
 */
class Player extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'tbl_playerdata';

    /**
     * @var string
     */
    protected $primaryKey = 'PlayerID';

    /**
     * @var array
     */
    protected $guarded = ['PlayerID'];

    /**
     * @var array
     */
    protected $with = ['game', 'battlelog'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'GameID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recordsBy()
    {
        return $this->hasMany(Adkats\Record::class, 'source_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recordsAgainst()
    {
        return $this->hasMany(Adkats\Record::class, 'target_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function battlelog()
    {
        return $this->hasOne(Battlelog::class, 'player_id');
    }

    /**
     * Does the player have a battlelog persona id linked.
     *
     * @return bool
     */
    public function hasPersona(): bool
    {
        return ! empty($this->battlelog);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function stats()
    {
        return $this->hasManyThrough(Player\Stat::class, Player\Server::class, 'PlayerID', 'StatsID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function sessions()
    {
        return $this->hasManyThrough(Player\Session::class, Player\Server::class, 'PlayerID', 'StatsID');
    }
}
