<?php

namespace BFACP\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

/**
 * Class Server
 * @package BFACP\Http\Resources
 */
class Server extends Resource
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
            'id'        => $this->ServerID,
            'ip'        => $this->ip,
            'rcon_port' => $this->port,
            'name'      => $this->ServerName,
            'game'      => [
                'id'    => $this->game->GameID,
                'label' => $this->game->Name,
            ],
            'map'       => $this->mapName,
            'mode'      => $this->GameMode,
            'slug'      => $this->slug,
            'slots'     => [
                'max'    => $this->maxSlots,
                'active' => $this->usedSlots,
            ],
        ];
    }
}
