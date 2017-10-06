<?php

namespace BFACP\Libraries\Battlelog;

use BFACP\Exceptions\Adkats\BattlelogException;

/**
 * Class Server
 * @package BFACP\Libraries\Battlelog
 */
class Server extends BattlelogClient
{
    /**
     * @var
     */
    protected $battlelogServerInfo;

    /**
     * @var array
     */
    private $options = [
        'filtered'       => 1,
        'expand'         => 1,
        'settings'       => '',
        'useLocation'    => 1,
        'useAdvanced'    => 1,
        'gameexpansions' => -1,
        'q'              => '',
        'mapRotation'    => -1,
        'modeRotation'   => -1,
        'password'       => -1,
        'osls'           => -1,
        'vvsa'           => -1,
        'vffi'           => -1,
        'vaba'           => -1,
        'vkca'           => -1,
        'v3ca'           => -1,
        'v3sp'           => -1,
        'vmsp'           => -1,
        'vrhe'           => -1,
        'vhud'           => -1,
        'vmin'           => -1,
        'vnta'           => -1,
        'vbdm-min'       => 1,
        'vbdm-max'       => 300,
        'vprt-min'       => 1,
        'vprt-max'       => 300,
        'vshe-min'       => 1,
        'vshe-max'       => 300,
        'vtkk-min'       => 1,
        'vttk-max'       => 99,
        'vnit-min'       => 30,
        'vnit-max'       => 86400,
        'vtkc-min'       => 1,
        'vtkc-max'       => 99,
        'vvsd-min'       => 0,
        'vvsd-max'       => 500,
        'vgmc-min'       => 0,
        'vgmc-max'       => 500,
    ];

    /**
     * @return mixed
     */
    public function getBattlelogServerInfo()
    {
        return $this->cache->get('battlelog.server.' . $this->server->id);
    }

    /**
     * @param mixed $battlelogServerInfo
     *
     * @return Server
     */
    public function setBattlelogServerInfo($battlelogServerInfo)
    {
        $this->battlelogServerInfo = $this->cache->remember('battlelog.server.' . $this->server->id, 5,
            function () use ($battlelogServerInfo) {
                return $battlelogServerInfo;
            });

        return $this;
    }

    /**
     * @return string
     */
    public function getBattlelogServerGuid(): string
    {
        return $this->battlelogServerInfo['guid'];
    }

    /**
     * @return array
     */
    public function getOnlinePlayers()
    {
        $slots = $this->battlelogServerInfo['slots'];

        return [
            'in_queue'   => isset($slots[1]) ? $slots[1] : ['current' => 0, 'max' => 0],
            'players'    => isset($slots[2]) ? $slots[2] : ['current' => 0, 'max' => 0],
            'spectators' => isset($slots[8]) ? $slots[8] : ['current' => 0, 'max' => 0],
            'commanders' => isset($slots[4]) ? $slots[4] : ['current' => 0, 'max' => 0],
        ];
    }

    /**
     * @return $this
     * @throws \Throwable
     */
    public function getServerInfo()
    {
        if (! empty($this->battlelogServerInfo)) {
            return $this->getBattlelogServerInfo();
        }

        $this->options['q'] = $this->server->ServerName;
        $uri = sprintf($this->getUris()['generic']['servers']['server_browser'], $this->getGame(),
            http_build_query($this->options));

        $response = $this->buildRequestAndSend($uri);

        throw_unless(array_key_exists('servers', $response['globalContext']),
            new BattlelogException('Error with battlelog response.'));

        $servers = $response['globalContext']['servers'];

        throw_if(empty($servers), new BattlelogException('No servers were returned.'));

        if (count($servers) > 1) {
            foreach ($servers as $server) {
                if ($server['name'] == $this->server->ServerName) {
                    $this->setBattlelogServerInfo($server);
                    break;
                }
            }
        } else {
            $this->setBattlelogServerInfo($servers[0]);
        }

        return $this->getBattlelogServerInfo();
    }

    /**
     * @param $uri
     *
     * @return mixed
     * @throws \Throwable
     */
    private function buildRequestAndSend($uri)
    {
        throw_if(is_null($this->server), (new BattlelogException('No server has been set.')));

        return $this->sendRequest($uri);
    }
}
