<?php

namespace BFACP\Http\Controllers\Player;

use Illuminate\Http\Request;
use BFACP\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

/**
 * Class ListingController
 * @package BFACP\Http\Controllers\Player
 */
class ListingController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showListing(Request $request)
    {
        $request = $request->create(route('player.index', [], false));
        $response = Route::dispatch($request);

        return view('players.player-listing', ['response' => $response]);
    }
}
