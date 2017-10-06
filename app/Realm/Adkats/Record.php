<?php

namespace BFACP\Realm\Adkats;

use BFACP\Realm\Player;
use BFACP\Realm\Server;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Record
 * @package BFACP\Realm\Adkats
 */
class Record extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'adkats_records_main';

    /**
     * @var string
     */
    protected $primaryKey = 'record_id';

    /**
     * @var array
     */
    protected $guarded = ['record_id'];

    /**
     * @var array
     */
    protected $dates = ['record_time'];

    /**
     * @var array
     */
    protected $appends = ['is_external_record', 'stamp'];

    /**
     * @var array
     */
    protected $with = ['server', 'targetPlayer', 'sourcePlayer', 'commandType', 'commandAction'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function targetPlayer()
    {
        return $this->belongsTo(Player::class, 'target_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sourcePlayer()
    {
        return $this->belongsTo(Player::class, 'source_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function commandType()
    {
        return $this->belongsTo(Command::class, 'command_type');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function commandAction()
    {
        return $this->belongsTo(Command::class, 'command_action');
    }

    /**
     * @return bool
     */
    public function getIsExternalRecordAttribute()
    {
        return $this->attributes['adkats_web'] == 1;
    }

    /**
     * @return mixed
     */
    public function getStampAttribute()
    {
        return $this->record_time->toIso8601String();
    }
}
