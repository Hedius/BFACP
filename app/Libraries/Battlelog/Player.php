<?php

namespace BFACP\Libraries\Battlelog;

use BFACP\Exceptions\Adkats\BattlelogException;
use BFACP\Realm\Adkats\Battlelog;

/**
 * Class Player
 * @package BFACP\Libraries\Battlelog
 */
class Player extends BattlelogClient
{
    /**
     * @var array
     */
    protected $battlelogPlayerInfo;

    /**
     * @return mixed
     */
    public function getBattlelogPlayerInfo()
    {
        return $this->battlelogPlayerInfo;
    }

    /**
     * @param mixed $battlelogPlayerInfo
     *
     * @return Player
     */
    public function setBattlelogPlayerInfo($battlelogPlayerInfo)
    {
        $this->battlelogPlayerInfo = $battlelogPlayerInfo;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlayerInfo()
    {
        $uri = sprintf($this->getUris()['generic']['profile'], $this->getGame(), $this->player->SoldierName);

        $response = $this->buildRequestAndSend($uri);

        throw_unless(array_key_exists('profilePersonas', $response['context']),
            new BattlelogException(sprintf('No player by the name "%s" was found on battlelog.',
                $this->player->SoldierName)));

        $playerGravatar = $response['context']['profileCommon']['user']['gravatarMd5'];
        $soldiers = $response['context']['soldiersBox'];

        foreach ($response['context']['profilePersonas'] as $persona) {
            // Only save the PC version of the battlelog persona.
            if ($persona['namespace'] == 'cem_ea_id') {
                $this->saveAndUpdate($persona, $playerGravatar, $this->checkBanStatus($soldiers));
                break;
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    private function buildRequestAndSend($uri)
    {
        throw_if(is_null($this->player), (new BattlelogException('No player has been set.')));

        return $this->sendRequest($uri);
    }

    /**
     * @param array $persona
     * @param       $gravatar
     * @param       $isPersonaBanned
     *
     * @return $this
     */
    private function saveAndUpdate(array $persona, $gravatar, $isPersonaBanned)
    {
        if (! $this->player->hasPersona()) {
            $this->player->battlelog()->save(new Battlelog([
                'gravatar'       => $gravatar,
                'persona_banned' => $isPersonaBanned,
                'persona_id'     => $persona['personaId'],
                'user_id'        => $persona['userId'],
            ]));

            $this->player->load('battlelog');

            return $this;
        }

        $battlelogModel = $this->player->battlelog;
        $battlelogModel->persona_banned = $isPersonaBanned;
        $battlelogModel->gravatar = $gravatar;
        $battlelogModel->persona_id = $persona['personaId'];
        $battlelogModel->user_id = $persona['userId'];
        $this->player->battlelog()->save($battlelogModel);

        $this->player->load('battlelog');

        return $this;
    }

    /**
     * @param $soldiers
     *
     * @return bool
     */
    private function checkBanStatus($soldiers)
    {
        foreach ($soldiers as $soldier) {
            if ($soldier['persona']['namespace'] == 'cem_ea_id' && $soldier['game'] == $this->getGames()[$this->getGame()]) {
                return $soldier['isPersonaBanned'];
            }
        }

        return false;
    }
}
