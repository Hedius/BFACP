<?php

namespace BFACP\Realm;

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'GameID');
    }
}
