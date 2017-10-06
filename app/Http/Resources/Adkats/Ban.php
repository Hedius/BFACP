<?php

namespace BFACP\Http\Resources\Adkats;

use BFACP\Http\Resources\Adkats\Record as RecordResource;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class Ban
 * @package BFACP\Http\Resources\Adkats
 */
class Ban extends Resource
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
            'id'               => $this->ban_id,
            'notes'            => $this->ban_notes,
            'status'           => [
                'active'    => $this->is_active,
                'expired'   => $this->is_expired,
                'unbanned'  => $this->is_unbanned,
                'permament' => $this->is_perm,
            ],
            'enforcement_type' => [
                'name' => $this->ban_enforceName,
                'guid' => $this->ban_enforceGUID,
                'ip'   => $this->ban_enforceIP,
            ],
            'stamps'           => [
                'issued'  => $this->ban_issued,
                'expires' => $this->ban_expires,
            ],
            'record'           => (new RecordResource($this->record)),
        ];
    }
}
