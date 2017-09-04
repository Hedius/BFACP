<?php

namespace BFACP\Http\Resources\Adkats;

use Illuminate\Http\Resources\Json\Resource;

/**
 * Class Command
 * @package BFACP\Http\Resources\Adkats
 */
class Command extends Resource
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
            'id' => $this->command_id,
            'name' => $this->command_name
        ];
    }
}
