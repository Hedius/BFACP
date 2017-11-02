<?php

namespace BFACP\Libraries\Battlelog;


use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class BattlelogClient
 * @package BFACP\Libraries\Battlelog
 */
class BattlelogClient
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \BFACP\Realm\Server|null
     */
    protected $server = null;

    /**
     * @var \BFACP\Realm\Player|null
     */
    protected $player = null;

    /**
     * @var string
     */
    protected $game = '';

    /**
     * @var array
     */
    protected $uris = [
        'bf4'     => [
            'overview'      => '%s/warsawoverviewpopulate/%u/1/',
            'weapons'       => '%s/warsawWeaponsPopulateStats/%u/1/stats/',
            'vehicles'      => '%s/warsawvehiclesPopulateStats/%u/1/stats/',
            'battlereports' => '%s/warsawbattlereportspopulate/%u/2048/1/',
            'battlereport'  => '%s/battlereport/loadgeneralreport/%s/1/%u/',
            'soldier'       => '%s/soldier/%u/stats/%u/pc/',
        ],
        'bf3'     => [
            'overview'     => '%s/overviewPopulateStats/%u/bf3-ru-assault/1/',
            'weapons'      => '%s/weaponsPopulateStats/%u/1/stats/',
            'vehicles'     => '%s/vehiclesPopulateStats/%u/1/stats/',
            //'battlereports' => '%s/warsawbattlereportspopulate/%u/2048/1/',
            'battlereport' => '%s/battlereport/loadplayerreport/%s/1/%u/',
            'soldier'      => '%s/soldier/%u/stats/%u/pc/',
        ],
        'bfh'     => [
            'overview'      => '%s/bfhoverviewpopulate/%u/1/',
            'weapons'       => '%s/BFHWeaponsPopulateStats/%u/1/stats/',
            'vehicles'      => '%s/bfhvehiclesPopulateStats/%u/1/stats/',
            'battlereports' => '%s/warsawbattlereportspopulate/%u/8192/1/',
            'soldier'       => '%s/soldier/%u/stats/%u/pc/',
        ],
        'generic' => [
            'profile' => '%s/user/%s',
            'servers' => [
                'players_online' => '%s/servers/getNumPlayersOnServer/pc/%s',
                'server_browser' => '%s/servers/pc/?%s',
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $games = [
        'bfh' => 8192,
        'bf4' => 2048,
        'bf3' => 2,
    ];

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var string
     */
    private $battlelogUrl = 'http://battlelog.battlefield.com/';

    /**
     * BattlelogClient constructor.
     *
     * @param \GuzzleHttp\Client           $guzzle
     * @param \Illuminate\Cache\Repository $cache
     */
    public function __construct(Guzzle $guzzle, Cache $cache)
    {
        $this->client = $guzzle;
        $this->cache = $cache;
    }

    /**
     * @param null $pluck
     *
     * @return array|string
     */
    public function getUris($pluck = null)
    {
        if (! is_null($pluck) && is_string($pluck)) {
            $uriValue = array_get(array_dot($this->uris), $pluck);

            return $uriValue;
        }

        return $this->uris;
    }

    /**
     * @return array
     */
    public function getGames(): array
    {
        return $this->games;
    }

    /**
     * @return \BFACP\Realm\Server
     */
    public function getServer(): \BFACP\Realm\Server
    {
        return $this->server;
    }

    /**
     * @param \BFACP\Realm\Server $server
     *
     * @return BattlelogClient
     */
    public function setServer(\BFACP\Realm\Server $server): BattlelogClient
    {
        $this->server = $server;
        $this->setGame($server->game->Name);

        return $this;
    }

    /**
     * @return \BFACP\Realm\Player
     */
    public function getPlayer(): \BFACP\Realm\Player
    {
        return $this->player;
    }

    /**
     * @param \BFACP\Realm\Player $player
     *
     * @return BattlelogClient
     */
    public function setPlayer(\BFACP\Realm\Player $player): BattlelogClient
    {
        $this->player = $player;
        $this->setGame($player->game->Name);

        return $this;
    }

    /**
     * @param bool $skipRename
     *
     * @return string
     */
    public function getGame($skipRename = false): string
    {
        if ($skipRename) {
            return strtoupper(str_replace('bfh', 'BFHL', $this->game));
        }

        return $this->game;
    }

    /**
     * @param string $game
     *
     * @return BattlelogClient
     */
    public function setGame(string $game): BattlelogClient
    {
        $this->game = strtolower(str_replace('BFHL', 'bfh', $game));

        return $this;
    }

    /**
     * @param null|string $uri
     *
     * @return string
     */
    public function getBattlelogUrl($uri = null): string
    {
        return is_null($uri) ? $this->battlelogUrl : $this->battlelogUrl . $uri;
    }

    /**
     * @param string $uri
     *
     * @return array
     */
    protected function sendRequest($uri)
    {
        try {
            $response = $this->client->get($this->getBattlelogUrl($uri), [
                'headers' => [
                    'X-AjaxNavigation' => true,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error(sprintf('Request to "%s" failed.', $this->getBattlelogUrl($uri)));
            Log::critical(sprintf('Request to "%s" failed. Reason: %s', $this->getBattlelogUrl($uri),
                $e->getMessage()));
            throw $e;
        }
    }
}
