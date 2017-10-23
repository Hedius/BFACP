<?php

namespace BFACP\Http\Resources\Player;

use BFACP\Http\Resources\Server as ServerResource;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class Session
 * @package BFACP\Http\Resources\Player
 */
class Session extends Resource
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
            'id'         => $this->SessionID,
            'score'      => $this->Score,
            'kills'      => $this->Kills,
            'headshots'  => $this->Headshots,
            'deaths'     => $this->Deaths,
            'suicides'   => $this->Suicide,
            'teamkills'  => $this->TKs,
            'playtime'   => $this->Playtime,
            'rounds'     => $this->RoundCount,
            'streaks'    => [
                'kills'  => $this->Killstreak,
                'deaths' => $this->Deathstreak,
            ],
            'scores'     => [
                'total' => $this->Score,
                'high'  => $this->HighScore,
            ],
            'wins'       => $this->Wins,
            'losses'     => $this->Losses,
            'timestamps' => [
                'start' => $this->session_start,
                'end'   => $this->session_end,
            ],
            'server'     => (ServerResource::collection($this->whenLoaded('server'))),
        ];
    }
}
