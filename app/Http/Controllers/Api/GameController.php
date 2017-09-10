<?php

namespace BFACP\Http\Controllers\Api;

use BFACP\Http\Controllers\Controller;
use BFACP\Http\Resources\Game as GameResource;
use BFACP\Realm\Game;

/**
 * Class GameController
 * @package BFACP\Http\Controllers\Api
 */
class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \BFACP\Realm\Game $game
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Game $game)
    {
        return response()->success(null, (GameResource::collection($game->all())));
    }

    /**
     * Display the specified resource.
     *
     * @param  \BFACP\Realm\Game $game
     *
     * @return \BFACP\Http\Resources\Game
     */
    public function show(Game $game)
    {
        return response()->success(null, (new GameResource($game)));
    }
}
