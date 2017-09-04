<?php

namespace BFACP\Realm;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Game
 * @package BFACP\Realm
 */
class Game extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'tbl_games';

    /**
     * @var string
     */
    protected $primaryKey = 'GameID';

    /**
     * @var array
     */
    protected $guarded = ['*'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class, 'GameID');
    }

}
