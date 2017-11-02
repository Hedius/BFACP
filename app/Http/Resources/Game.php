<?php

namespace BFACP\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

/**
 * Class Game
 * @package BFACP\Http\Resources
 */
class Game extends Resource
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
            'id'         => $this->GameID,
            'label'      => $this->Name,
            'chip_class' => $this->chip_class,
            'servers'    => Server::collection($this->whenLoaded('servers')),
        ];
    }
}
