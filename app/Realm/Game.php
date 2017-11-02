<?php

namespace BFACP\Realm;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Game
 * @package BFACP\Realm
 * @property integer $GameID
 * @property string  $Name
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
     * @var array
     */
    protected $appends = ['chip_class'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class, 'GameID');
    }

    /**
     * @return string
     */
    public function getChipClassAttribute()
    {
        $chipColor = 'white-text';

        switch ($this->Name) {
            case "BF3":
                $chipColor .= ' cyan darken-3';
                break;
            case "BF4":
                $chipColor .= ' teal darken-3';
                break;
            case "BFHL":
                $chipColor .= ' blue darken-3';
                break;
            case "BFBC2":
                $chipColor .= ' indigo darken-1';
                break;
            default:
                $chipColor .= ' grey darken-4';
        }

        return $chipColor;
    }
}
