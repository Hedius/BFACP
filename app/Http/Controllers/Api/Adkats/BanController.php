<?php

namespace BFACP\Http\Controllers\Api\Adkats;

use BFACP\Http\Controllers\Controller;
use BFACP\Realm\Adkats\Ban;
use Illuminate\Http\Request;
use BFACP\Http\Resources\Adkats\Ban as BanResource;

/**
 * Class BanController
 * @package BFACP\Http\Controllers\Api\Adkats
 */
class BanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Ban $ban)
    {
        return BanResource::collection($ban->orderBy('ban_id', 'desc')->paginate(30));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \BFACP\Realm\Adkats\Ban $ban
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Ban $ban)
    {
        return response()->success(null, (new BanResource($ban)));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \BFACP\Realm\Adkats\Ban $ban
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Ban $ban)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \BFACP\Realm\Adkats\Ban  $ban
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Ban $ban)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \BFACP\Realm\Adkats\Ban $ban
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ban $ban)
    {
        //
    }
}
