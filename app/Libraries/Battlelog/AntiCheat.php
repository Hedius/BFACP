<?php

namespace BFACP\Libraries\Battlelog;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;


/**
 * Class AntiCheat
 * @package BFACP\Libraries\Battlelog
 */
class AntiCheat extends Player
{
    /**
     * Categories allowed to be parsed.
     *
     * @var array
     */
    protected $whitelist = [
        'BF3' => [
            'carbines',
            'machine_guns',
            'assault_rifles',
            'sub_machine_guns',
            'handheld_weapons',
        ],
        'BF4' => [
            'carbines',
            'lmgs',
            'assault_rifles',
            'pdws',
            'handguns',
            'shotguns',
            'sniper_rifles',
            'dmrs',
        ],
        'BFH' => [
            'assault_rifles',
            'ar_standard',
            'sr_standard',
            'br_standard',
            'handguns',
            'pistols',
            'machine_pistols',
            'revolvers',
            'shotguns',
            'smg_mechanic',
            'sg_enforcer',
            'smg',
        ],
    ];

    /**
     * @var array
     */
    protected $weapons = [];

    /**
     * @var array
     */
    protected $weaponsDetected = [];

    /**
     * @var array
     */
    private $_links = [
        'weapon_damages' => [
            'primary' => 'https://raw.githubusercontent.com/AdKats/AdKats/master/adkatsblweaponstats.json',
            'backup'  => 'http://adkats.gamerethos.net/api/fetch/weapons',
        ],
    ];

    /**
     * Trigger values.
     *
     * @var array
     */
    private $triggers = [
        'DPS'   => 60,
        'HKP'   => 40,
        'KPM'   => 4.5,
        'Kills' => 50,
    ];

    /**
     * AntiCheat constructor.
     *
     * @param \GuzzleHttp\Client           $guzzle
     * @param \Illuminate\Cache\Repository $cache
     */
    public function __construct(\GuzzleHttp\Client $guzzle, \Illuminate\Cache\Repository $cache)
    {
        parent::__construct($guzzle, $cache);

        $this->getWeaponDamages();
    }

    /**
     * @return array
     */
    public function getWeaponsDetected(): array
    {
        return $this->weaponsDetected;
    }

    /**
     * @return array
     */
    public function getWeapons(): array
    {
        return $this->weapons;
    }

    /**
     * @param array $weapons
     *
     * @return AntiCheat
     */
    public function setWeapons(array $weapons): AntiCheat
    {
        $this->weapons = $weapons;

        return $this;
    }

    /**
     * @param $weapons
     *
     * @return $this
     */
    public function parse($weapons)
    {
        foreach ($weapons as $weapon) {
            if ($this->checkWhitelist($weapon)) {
                continue;
            }

            $checks = [
                'dps' => $this->checkDPS($weapon),
                'kpm' => $this->checkKPM($weapon),
                'hkp' => $this->checkHKP($weapon),
            ];

            foreach ($checks as $check) {
                if ($check) {
                    $this->weaponsDetected[] = $weapon + ['checks' => $checks];
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * @return \BFACP\Libraries\Battlelog\AntiCheat
     */
    private function getWeaponDamages()
    {
        $this->setWeapons($this->cache->remember('acs.weapons', 60 * 24, function () {
            try {
                $response = $this->client->get($this->_links['weapon_damages']['primary']);
            } catch (RequestException $e) {
                Log::error('Failed to get weapon damages from github. Trying backup location..');
                try {
                    $response = $this->client->get($this->_links['weapon_damages']['backup']);
                } catch (RequestException $e) {
                    Log::critical('Failed to get weapon damages from backup location. Reason: ' . $e->getMessage());
                    throw $e;
                }
            }

            return json_decode($response->getBody(), true);
        }));

        return $this;
    }

    /**
     * @param $weapon
     *
     * @return bool
     */
    private function checkWhitelist($weapon)
    {
        $category = $this->getWeaponCategory($weapon);

        return (! in_array($category, $this->whitelist[$this->getGame(true)]) || ! array_key_exists($category,
                $this->weapons[$this->getGame(true)]) || ! array_key_exists($weapon['slug'],
                $this->weapons[$this->getGame(true)][$category])
        );
    }

    /**
     * @param $weapon
     *
     * @return mixed
     */
    private function getWeaponCategory($weapon)
    {
        return str_replace(' ', '_', strtolower(trim($weapon['category'])));
    }

    /**
     * @param $weapon
     *
     * @return bool
     */
    private function checkDPS($weapon)
    {
        $weaponDPS = $this->weapons[$this->getGame(true)][$this->getWeaponCategory($weapon)][$weapon['slug']];

        $diff = 1 - divide(($weaponDPS['max'] - $weapon['dps']), $weaponDPS['max']);

        return ($diff > 1.5 && $weapon['kills'] >= $this->triggers['Kills']);
    }

    /**
     * @param $weapon
     *
     * @return bool
     */
    private function checkKPM($weapon)
    {
        return ($weapon['kpm'] >= $this->triggers['KPM'] && $weapon['kills'] >= $this->triggers['Kills']);
    }

    /**
     * @param $weapon
     *
     * @return bool
     */
    private function checkHKP($weapon)
    {
        return ($weapon['hskp'] >= $this->triggers['HKP'] && $weapon['kills'] >= $this->triggers['Kills']);
    }
}
