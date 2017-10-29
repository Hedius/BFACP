<?php

namespace BFACP\Http\Controllers\Api;

use BFACP\Http\Resources\Scoreboard as ScoreboardResource;
use BFACP\Libraries\BattlefieldConn;
use BFACP\Realm\Server;
use Illuminate\Http\Request;
use BFACP\Http\Controllers\Controller;
use BFACP\Http\Resources\Server as ServerResource;
use Illuminate\Support\Collection;

/**
 * Class ScoreboardController
 * @package BFACP\Http\Controllers\Api
 */
class ScoreboardController extends Controller
{
    /**
     * @var Collection
     */
    private $collection;

    public function __construct()
    {
        $this->collection = new Collection();
    }

    public function showLive(Server $server)
    {
        $bfc = new BattlefieldConn($server);
        $bfc->loginSecure('9UTEYoVW');

        return new ScoreboardResource($bfc);
    }

    public function showDbLive(Server $server)
    {
        return $server;
    }
}
