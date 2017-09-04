<?php

use BFACP\Http\Resources\Game as GameResource;
use BFACP\Http\Resources\Player as PlayerResource;
use BFACP\Http\Resources\Server as ServerResource;
use BFACP\Http\Resources\Adkats\Record as RecordResource;
use BFACP\Realm\Adkats\Record;
use BFACP\Realm\Game;
use BFACP\Realm\Player;
use BFACP\Realm\Server;

Route::get('player/{id?}', function ($id = null) {
    if (! empty($id)) {
        return new PlayerResource(Player::findOrFail($id));
    }

    return PlayerResource::collection(Player::paginate(100));
})->where('id', '[0-9]+');

Route::get('game/{name?}', function ($name = null) {
    if (! empty($name)) {
        return new GameResource(Game::where('Name', $name)->firstOrFail());
    }

    return GameResource::collection(Game::all());
})->where('name', '[A-Za-z]+');

Route::get('server/{id?}', function ($id = null) {
    if (! empty($id)) {
        return new ServerResource(Server::findOrFail($id));
    }

    return ServerResource::collection(Server::paginate(30));
})->where('id', '[0-9]+');

Route::get('record/{id?}', function ($id = null) {
    if (! empty($id)) {
        return new RecordResource(Record::findOrFail($id));
    }

    return RecordResource::collection(Record::paginate(30));
})->where('id', '[0-9]+');
