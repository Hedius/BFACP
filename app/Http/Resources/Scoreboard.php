<?php

namespace BFACP\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class Scoreboard extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name' => $this->getServername(),
            'version' => $this->getServerVersion(),
            'mode' => $this->getCurrentGameMode(),
            'map' => $this->getCurrentMap(),
            'players' => [
                'online' => $this->getCurrentPlayers(),
                'max' => $this->getMaxPlayers(),
            ],
            'rounds' => [
                'played' => $this->getRoundsPlayed(),
                'total' => $this->getTotalRounds(),
            ],
            'scores' => $this->getTeamScores(),
            'uptime' => $this->getServerUptime(),
            'battlelog' => optional($this->getBattlelog())->getServerInfo(),
            'board' => $this->listPlayers(),
        ];
    }
}
