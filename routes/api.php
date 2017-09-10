<?php

Route::namespace('Api')->group(function () {

    Route::get('player/{player}/records/issued', 'PlayerController@showRecordsByPlayer');

    Route::get('player/{player}/records/against', 'PlayerController@showRecordsAgainstPlayer');

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
});
