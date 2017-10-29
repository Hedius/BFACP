<?php

namespace BFACP\Http\Controllers\Api;

use BFACP\Exceptions\Adkats\BattlelogException;
use BFACP\Http\Controllers\Controller;
use BFACP\Http\Resources\Adkats\Record as RecordResource;
use BFACP\Http\Resources\Player as PlayerResource;
use BFACP\Libraries\Battlelog\AntiCheat;
use BFACP\Realm\Player;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;

/**
 * Class PlayerController
 * @package BFACP\Http\Controllers\Api
 */
class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \BFACP\Realm\Player $player
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Player $player)
    {
        if (($queryLimit = request()->get('take', 30)) > 200) {
            $queryLimit = 200;
        }

        if (request()->has('playerName')) {
            $players = $player->where('SoldierName', 'LIKE', request()->get('playerName') . '%');
        } else {
            $players = $player->orderBy('PlayerID', 'desc');
        }

        return PlayerResource::collection($players->paginate($queryLimit));
    }

    /**
     * Display the specified resource.
     *
     * @param  \BFACP\Realm\Player $player
     *
     * @return \BFACP\Http\Resources\Player
     */
    public function show(Request $request, Player $player)
    {
        try {
            if ($request->has('opts')) {
                $opts = explode(',', $request->get('opts'));
                $player->load($opts);
            }
        } catch (RelationNotFoundException $e) {
            // Catch relationship error and quietly ignore it.
        }

        return response()->success(null, (new PlayerResource($player)));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \BFACP\Realm\Player      $player
     *
     * @return mixed
     */
    public function showRecordsByPlayer(Request $request, Player $player)
    {
        $resultCollection = $player->recordsBy()->orderBy('record_id', 'desc');

        // Filters the results to only show certain commands issued by the player.
        if ($request->has('filter')) {
            $commandIds = explode(',', $request->get('filter'));

            $resultCollection = $resultCollection->whereIn('command_type', $commandIds);
        }

        return RecordResource::collection($resultCollection->paginate(30));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \BFACP\Realm\Player      $player
     *
     * @return mixed
     */
    public function showRecordsAgainstPlayer(Request $request, Player $player)
    {
        $resultCollection = $player->recordsAgainst()->orderBy('record_id', 'desc');

        // Filters the results to only show certain commands issued against the player.
        if ($request->has('filter')) {
            $commandIds = explode(',', $request->get('filter'));

            $resultCollection = $resultCollection->whereIn('command_action', $commandIds);
        }

        return RecordResource::collection($resultCollection->paginate(30));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \BFACP\Realm\Player      $player
     *
     * @return mixed
     */
    public function showAntiCheatData(Request $request, Player $player)
    {
        try {
            $battlelog = app(AntiCheat::class);
            $battlelog->setPlayer($player);

            if (! $player->hasPersona()) {
                $battlelog->getPlayerInfo();
            }
        } catch (BattlelogException $e) {
            return response()->error($e->getMessage());
        }

        return response()->success(null, $battlelog->parse($battlelog->getPlayerWeapons())->getWeaponsDetected());
    }
}
