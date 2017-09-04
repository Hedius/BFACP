<?php

namespace BFACP\Http\Resources;

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
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->PlayerID,
            'name' => $this->SoldierName,
            'clantag' => $this->ClanTag,
            'ip' => $this->IP_Address,
            'guids' => [
                'pb' => $this->PBGUID,
                'ea' => $this->EAGUID
            ],
            'meta' => [
                'discord_id' => isset($this->DiscordID) ? $this->DiscordID : null
            ]
        ];
    }
}
