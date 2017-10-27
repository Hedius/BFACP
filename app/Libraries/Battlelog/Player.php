<?php

namespace BFACP\Libraries\Battlelog;

use BFACP\Exceptions\Adkats\BattlelogException;
use BFACP\Http\Resources\Player as PlayerResource;
use BFACP\Realm\Adkats\Battlelog;
use Illuminate\Support\Collection;

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
     * @inheritDoc
     */
    public function __toString()
    {
        return json_encode((new PlayerResource($this->player)));
    }

    /**
     * @return $this
     * @throws \Throwable
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

        $this->setBattlelogPlayerInfo($response['context']);

        return $this;
    }

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
     * @return $this
     * @throws \Throwable
     */
    public function getPlayerSoldier()
    {
        $uri = sprintf($this->getUris($this->getGame() . '.soldier'), $this->getGame(),
            $this->player->battlelog->user_id, $this->player->battlelog->persona_id);

        $response = $this->buildRequestAndSend($uri);

        throw_unless(array_key_exists('statsPersona', $response['context']),
            new BattlelogException(sprintf('Unable to retrieve player stats for %s.',
                $this->player->SoldierName)));

        $persona = $response['context']['statsPersona'];

        $this->player->SoldierName = $persona['personaName'];
        $this->player->ClanTag = empty($persona['clanTag']) ? null : $persona['clanTag'];
        $this->player->save();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlayerWeapons()
    {
        return $this->cache->remember(sprintf('battlelog.acs.%u', $this->player->PlayerID), 5, function () {
            $uri = sprintf($this->getUris($this->getGame() . '.weapons'), $this->getGame(),
                $this->player->battlelog->persona_id);

            $response = $this->buildRequestAndSend($uri)['data'];

            $weapons = new Collection();

            foreach ($response['mainWeaponStats'] as $weapon) {
                $weapons->push([
                    'slug'         => $weapon['slug'],
                    'category'     => $weapon['category'],
                    'headshots'    => $weapon['headshots'],
                    'kills'        => $weapon['kills'],
                    'deaths'       => $weapon['deaths'],
                    'score'        => $weapon['score'],
                    'fired'        => $weapon['shotsFired'],
                    'hit'          => $weapon['shotsHit'],
                    'timeEquipped' => $weapon['timeEquipped'],
                    'serviceStars' => $weapon['serviceStars'],
                    'accuracy'     => percent($weapon['shotsHit'], $weapon['shotsFired']),
                    'kpm'          => divide($weapon['kills'], divide($weapon['timeEquipped'], 60)),
                    'hskp'         => percent($weapon['headshots'], $weapon['kills']),
                    'dps'          => percent($weapon['kills'], $weapon['shotsHit']),
                    'weapon_link'  => $this->getWeaponUri($weapon['slug']),
                ]);
            }

            return $weapons;
        });
    }

    /**
     * @return \Illuminate\Support\Collection
     * @throws \Throwable
     */
    public function getPlayerBattleReports()
    {
        throw_if($this->getGame() == 'bf3',
            new BattlelogException('Battle Reports are not supported for Battlefield 3'));

        $uri = sprintf($this->getUris($this->getGame() . '.battlereports'), $this->getGame(),
            $this->player->battlelog->persona_id);

        $response = $this->buildRequestAndSend($uri)['data'];

        $battlereports = new Collection();

        if (array_key_exists('gameReports', $response)) {
            foreach ($response['gameReports'] as $report) {
                $battlereports->push($report);
            }
        }

        return $battlereports;
    }

    /**
     * @param $reportId
     *
     * @return \Illuminate\Support\Collection
     * @throws \Throwable
     */
    public function getPlayerBattleReport($reportId)
    {
        throw_if($this->getGame() == 'bfh',
            new BattlelogException('Battle Reports are not supported for Battlefield Hardline'));

        $uri = sprintf($this->getUris($this->getGame() . '.battlereport'), $this->getGame(), $reportId,
            $this->player->battlelog->persona_id);

        $response = $this->buildRequestAndSend($uri);

        $battlereport = new Collection([
            'game' => $this->getGame(),
            'link' => $this->getBattlelogUrl(sprintf('%s/battlereport/show/1/%s/%u', $this->getGame(), $reportId,
                $this->player->battlelog->persona_id)),
            'data' => $response,
        ]);

        return $battlereport;
    }

    /**
     * @param $uri
     *
     * @return mixed
     * @throws \Throwable
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

    /**
     * @param $slug
     *
     * @return string
     */
    private function getWeaponUri($slug): string
    {
        if ($this->getGame() == 'bf3') {
            return $this->getBattlelogUrl(sprintf('%s/soldier/%s/iteminfo/%s/%u/pc/', $this->getGame(),
                $this->player->SoldierName,
                strtolower($slug), $this->player->battlelog->persona_id));
        }

        return $this->getBattlelogUrl(sprintf('%s/soldier/%s/weapons/%u/pc/#%s', $this->getGame(),
            $this->player->SoldierName,
            $this->player->battlelog->persona_id, strtolower($slug)));
    }
}
