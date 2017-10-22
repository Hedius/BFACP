<?php

namespace BFACP\Http\Controllers\Player;

use BFACP\Http\Controllers\Controller;

/**
 * Class ListingController
 * @package BFACP\Http\Controllers\Player
 */
class ListingController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showListing()
    {
        return view('players.player-listing');
    }
}
