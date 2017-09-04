<?php

namespace BFACP\Realm;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Server
 * @package BFACP\Realm
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
    protected $table = 'tbl_server';

    /**
     * @var string
     */
    protected $primaryKey = 'ServerID';

    /**
     * @var array
     */
    protected $guarded = ['ServerID'];

    /**
     * @var array
     */
    protected $with = ['game'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'GameID');
    }

    /**
     * Gets the IP Address.
     *
     * @return string
     */
    public function getIPAttribute()
    {
        $host = explode(':', $this->IP_Address)[0];
        return gethostbyname($host);
    }
    /**
     * Gets the RCON port from the IP Address.
     *
     * @return int
     */
    public function getPortAttribute()
    {
        $port = explode(':', $this->IP_Address)[1];
        return (int) $port;
    }

    /**
     * @return string
     */
    public function getSlugAttribute()
    {
        return str_slug($this->ServerName);
    }
}
