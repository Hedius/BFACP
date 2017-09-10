<?php

namespace BFACP\Http\Controllers\Api;

use BFACP\Http\Controllers\Controller;
use BFACP\Http\Resources\Adkats\Record as RecordResource;
use BFACP\Http\Resources\Player as PlayerResource;
use BFACP\Realm\Player;
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
        return PlayerResource::collection($player->paginate(100));
    }

    /**
     * Display the specified resource.
     *
     * @param  \BFACP\Realm\Player $player
     *
     * @return \BFACP\Http\Resources\Player
     */
    public function show(Player $player)
    {
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
}
