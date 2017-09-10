<?php

namespace BFACP\Http\Controllers\Api;

use BFACP\Http\Controllers\Controller;
use BFACP\Http\Resources\Server as ServerResource;
use BFACP\Realm\Server;
use Illuminate\Http\Request;

/**
 * Class ServerController
 * @package BFACP\Http\Controllers\Api
 */
class ServerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \BFACP\Realm\Server $server
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Server $server)
    {
        return ServerResource::collection($server->paginate(30));
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
     * @param  \BFACP\Realm\Server $server
     *
     * @return \BFACP\Http\Resources\Server
     */
    public function show(Server $server)
    {
        return response()->success(null, (new ServerResource($server)));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \BFACP\Realm\Server $server
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Server $server)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \BFACP\Realm\Server      $server
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Server $server)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \BFACP\Realm\Server $server
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Server $server)
    {
        //
    }
}
