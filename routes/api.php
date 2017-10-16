<?php

Route::namespace('Api')->group(function () {

    Route::get('player/{player}/records/issued', 'PlayerController@showRecordsByPlayer');

    Route::get('player/{player}/records/against', 'PlayerController@showRecordsAgainstPlayer');

    Route::get('player/{player}/acs', 'PlayerController@showAntiCheatData');

    Route::resource('player', 'PlayerController', [
        'only' => [
            'index',
            'show',
        ],
    ]);

    Route::resource('game', 'GameController', [
        'only' => [
            'index',
            'show',
        ],
    ]);

    Route::resource('server', 'ServerController', [
        'only' => [
            'index',
            'show',
        ],
    ]);

    Route::resource('record', 'RecordController', [
        'only' => [
            'index',
            'show',
            'update',
        ],
    ]);

    Route::resource('commands', 'Adkats\CommandsController', [
        'only' => [
            'index',
        ],
    ]);

    Route::resource('ban', 'Adkats\BanController');

    Route::get('battlelog', function () {
        $serversDisplay = [];
        $servers = \BFACP\Realm\Server::whereIn('ServerID', [3])->get();

        foreach ($servers as $server) {
            $rcon = new \BFACP\Libraries\BattlefieldConn($server);
            $rcon->loginSecure('9UTEYoVW');

            if($rcon->isLoggedIn()) {
                $serversDisplay[$server->game->Name][$server->slug] = [
                    'playerlist' => $rcon->listPlayers(),
                ];
                unset($rcon);
            }
        }

        return $serversDisplay;
    });
});
