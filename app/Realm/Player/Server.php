<?php

namespace BFACP\Realm\Player;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Server
 * @package BFACP\Realm\Player
 */
class Server extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'tbl_server_player';

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
    protected $dates = [];

    /**
     * @var array
     */
    protected $appends = [];

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(\BFACP\Realm\Server::class, 'ServerID');
    }
}
