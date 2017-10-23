<?php

namespace BFACP\Http\Resources\Player;

use BFACP\Http\Resources\Player as PlayerResource;
use BFACP\Http\Resources\Server;
use BFACP\Http\Resources\Server as ServerResource;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class Stat
 * @package BFACP\Http\Resources\Player
 */
class Stat extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'         => $this->StatsID,
            'score'      => $this->Score,
            'kills'      => $this->Kills,
            'deaths'     => $this->Deaths,
            'suicides'   => $this->Suicide,
            'teamkills'  => $this->TKs,
            'playtime'   => $this->Playtime,
            'rounds'     => $this->Rounds,
            'streaks'    => [
                'kills'  => $this->Killstreak,
                'deaths' => $this->Deathstreak,
            ],
            'scores'     => [
                'total' => $this->Score,
                'high'  => $this->HighScore,
                'rank'  => $this->rankScore,
            ],
            'wins'       => $this->Wins,
            'losses'     => $this->Losses,
            'timestamps' => [
                'start' => $this->first_seen,
                'end'   => $this->last_seen,
            ],
            'player'     => (new PlayerResource($this->whenLoaded('player'))),
            'server'     => (ServerResource::collection($this->whenLoaded('server'))),
        ];
    }
}
