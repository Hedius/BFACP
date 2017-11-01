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
            'game' => $this->getCurrentGame(),
            'players' => [
                'online' => $this->getCurrentPlayers(),
                'max' => $this->getMaxPlayers(),
                'battlelog' => optional($this->getBattlelog())->getOnlinePlayers(),
            ],
            'rounds' => [
                'played' => $this->getRoundsPlayed(),
                'total' => $this->getTotalRounds(),
            ],
            'scores' => $this->getTeamScores(),
            'uptime' => $this->getServerUptime(),
            'isRanked' => optional($this->getBattlelog())->isRanked(),
            'country' => optional($this->getBattlelog())->getServerCountry(),
            'tickrate' => optional($this->getBattlelog())->getServerTickRate(),
            'board' => $this->listPlayers(),
            //'battlelog' => optional($this->getBattlelog())->getServerInfo(),
        ];
    }
}
