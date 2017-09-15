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
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'        => $this->PlayerID,
            'name'      => $this->SoldierName,
            'clantag'   => $this->ClanTag,
            'ip'        => $this->IP_Address,
            'guids'     => [
                'pb' => $this->PBGUID,
                'ea' => $this->EAGUID,
            ],
            'battlelog' => [
                'persona_id' => $this->battlelog->persona_id,
                'user_id'    => (string) $this->battlelog->user_id,
                'is_banned'  => $this->battlelog->is_banned,
            ],
            'meta'      => [
                'discord_id' => isset($this->DiscordID) ? (int) $this->DiscordID : null,
            ],
        ];
    }
}
