<?php

namespace BFACP\Realm;

use BFACP\Realm\Adkats\Battlelog;
use BFACP\Realm\Adkats\Record;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Player
 * @package BFACP\Realm
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
        return $this->hasMany(Record::class, 'source_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recordsAgainst()
    {
        return $this->hasMany(Record::class, 'target_id');
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
}
