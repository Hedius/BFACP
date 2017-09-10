<?php

namespace BFACP\Http\Controllers\Api\Adkats;

use BFACP\Http\Controllers\Controller;
use BFACP\Realm\Adkats\Command;

/**
 * Class CommandsController
 * @package BFACP\Http\Controllers\Api\Adkats
 */
class CommandsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \BFACP\Realm\Adkats\Command $command
     *
     * @return \BFACP\Realm\Adkats\Command[]|\Illuminate\Database\Eloquent\Collection
     */
    public function index(Command $command)
    {
        return response()->success(null, ($command->where('command_active', '=', 'Active')->get()));
    }
}
