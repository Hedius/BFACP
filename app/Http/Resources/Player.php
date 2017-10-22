<?php

namespace BFACP\Http\Resources;

use BFACP\Http\Resources\Game as GameResource;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class Player
 * @package BFACP\Http\Resources
 */
class Player extends Resource
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
            'id'        => $this->PlayerID,
            'name'      => $this->SoldierName,
            'clantag'   => empty($this->ClanTag) ? null : $this->ClanTag,
            'ip'        => $this->IP_Address,
            'game'      => new GameResource($this->game),
            'guids'     => [
                'pb' => $this->PBGUID,
                'ea' => $this->EAGUID,
            ],
            'battlelog' => [
                'persona_id' => optional($this->battlelog)->persona_id,
                'user_id'    => optional($this->battlelog)->user_id,
                'is_banned'  => optional($this->battlelog)->is_banned,
            ],
            'meta'      => [
                'discord_id'   => optional($this->DiscordID),
                'country_code' => $this->CountryCode,
                'rank'         => $this->GlobalRank,
            ],
        ];
    }
}
