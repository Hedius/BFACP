<?php

namespace BFACP\Http\Controllers\Api;

use BFACP\Http\Controllers\Controller;
use BFACP\Http\Resources\Adkats\Record as RecordResource;
use BFACP\Http\Resources\Player as PlayerResource;
use BFACP\Realm\Player;

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
        return new PlayerResource($player);
    }

    /**
     * @param \BFACP\Realm\Player $player
     *
     * @return mixed
     */
    public function showRecords(Player $player)
    {
        $resultCollection = $player->recordsBy()->orderBy('record_id', 'desc')->paginate(30);
        return RecordResource::collection($resultCollection);
    }
}
