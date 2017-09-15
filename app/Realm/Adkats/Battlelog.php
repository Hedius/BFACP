<?php

namespace BFACP\Realm\Adkats;

use BFACP\Realm\Player;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Battlelog
 * @package BFACP\Realm\Adkats
 */
class Battlelog extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'adkats_battlelog_players';

    /**
     * @var string
     */
    protected $primaryKey = 'player_id';

    /**
     * @var array
     */
    protected $fillable = ['persona_id', 'gravatar', 'persona_banned', 'user_id'];

    /**
     * @var array
     */
    protected $appends = ['is_banned'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    /**
     * Is banned.
     *
     * @return bool
     */
    public function getIsBannedAttribute(): bool
    {
        return $this->attributes['persona_banned'];
    }
}
