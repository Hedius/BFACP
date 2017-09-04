<?php

namespace BFACP\Http\Resources\Adkats;

use BFACP\Http\Resources\Adkats\Command as CommandResource;
use BFACP\Http\Resources\Player as PlayerResource;
use BFACP\Http\Resources\Server as ServerResource;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class Record
 * @package BFACP\Http\Resources\Adkats
 */
class Record extends Resource
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
            'id'      => $this->record_id,
            'server'  => (new ServerResource($this->server)),
            'players' => [
                'target' => (! empty($this->targetPlayer) ? (new PlayerResource($this->targetPlayer)) : $this->target_name),
                'source' => (! empty($this->sourcePlayer) ? (new PlayerResource($this->sourcePlayer)) : $this->source_name),
            ],
            'command' => [
                'issued' => (new CommandResource($this->commandType)),
                'action' => (new CommandResource($this->commandAction)),
            ],
            'message' => $this->record_message,
            'meta'    => [
                'timestamp'          => $this->stamp,
                'is_external_record' => $this->is_external_record,
            ],
        ];
    }
}
