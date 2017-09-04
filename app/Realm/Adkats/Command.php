<?php

namespace BFACP\Realm\Adkats;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Command
 * @package BFACP\Realm\Adkats
 */
class Command extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'adkats_commands';

    /**
     * @var string
     */
    protected $primaryKey = 'command_id';

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
    protected $appends = ['is_interactive', 'is_enabled', 'is_invisible'];

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeGuest($query)
    {
        return $query->where('command_playerInteraction', false);
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeAdmin($query)
    {
        return $query->where('command_playerInteraction', true);
    }

    /**
     * @param $query
     * @param $type
     *
     * @return mixed
     */
    public function scopeType($query, $type)
    {
        return $query->where('command_active', $type);
    }

    /**
     * @return bool
     */
    public function getIsInteractiveAttribute()
    {
        return $this->attributes['command_playerInteraction'] == 1;
    }

    /**
     * @return bool
     */
    public function getIsEnabledAttribute()
    {
        return $this->attributes['command_active'] == 'Active';
    }

    /**
     * @return bool
     */
    public function getIsInvisibleAttribute()
    {
        return $this->attributes['command_active'] == 'Invisible';
    }
}
