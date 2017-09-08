<?php

Route::namespace('Api')->group(function () {

    Route::get('player/{player}/records', 'PlayerController@showRecords');
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
        ],
    ]);
});
