<?php

namespace BFACP\Libraries;


use BFACP\Exceptions\Adkats\RconException;
use BFACP\Http\Resources\Server as ServerResource;
use BFACP\Libraries\Battlelog\Server as BattlelogServer;
use BFACP\Realm\Server;
use Illuminate\Support\Facades\Log;

/**
 * Class BattlefieldConn
 * @package BFACP\Libraries
 */
class BattlefieldConn
{
    /**
     * The default response sent back by the game server.
     */
    const DEFAULT_GAME_SERVER_RESPONSE = 'OK';

    /**
     * Player not found response.
     */
    const PLAYER_NOT_FOUND = 'PlayerNotFoundError';

    /**
     * Team not found response.
     */
    const TEAM_NOT_FOUND = 'TeamNameNotFoundError';

    /**
     * Squad not found response.
     */
    const SQUAD_NOT_FOUND = 'SquadNameNotFoundError';

    /**
     * Game mode not found response.
     */
    const PLAYMODE_NOT_FOUND = 'PlaymodeNameNotFoundError';

    /**
     * Map not found response.
     */
    const MAPNAME_NOT_FOUND = 'MapNameNotFoundError';

    /**
     * Not auth for rcon commands response.
     */
    const NOT_LOGGED_IN = 'NotLoggedInAsAdmin';

    /**
     * Login failed response.
     */
    const LOGIN_FAILED = 'LoginFailed';

    /*
     * Invalid arguments response.
     */
    const INVALID_ARGUMENTS = 'InvalidArguments';

    /**
     * Invalid password response.
     */
    const INVALID_PASSWORD = 'InvalidPassword';

    /**
     * Invalid config response.
     */
    const INVALID_CONFIG = 'InvalidConfig';

    /**
     * Command disallowed response.
     */
    const COMMAND_DISALLOWED = 'CommandDisallowedOnRanked';

    /**
     * Command is read only.
     */
    const COMMAND_READ_ONLY = 'CommandIsReadOnly';

    /**
     * @var \BFACP\Realm\Server
     */
    protected $server;

    /**
     * @var array
     */
    protected $rconCache = [];

    /**
     * Stores the data from the server.
     *
     * @var array
     */
    protected $data = [
        'server'         => null,
        'server_version' => null,
        'players'        => null,
        'team_scores'    => [
            'team1' => 0,
            'team2' => 0,
            'team3' => 0,
            'team4' => 0,
        ],
        'players_parsed' => [],
        'server_parsed'  => [],
        'battlelog'      => [],
    ];

    /**
     * @var null
     */
    private $sock = null;

    /**
     * @var bool
     */
    private $connection = false;

    /**
     * @var bool
     */
    private $isLoggedIn = false;

    /**
     * @var int
     */
    private $clientSequenceNr = 0;

    /**
     * @var null|int
     */
    private $sockType = null;

    /**
     * @var \BFACP\Libraries\Battlelog\Server
     */
    private $battlelog;

    /**
     * @inheritDoc
     */
    public function __construct(Server $server, $debug = false)
    {
        $this->server = $server;
        $this->battlelog = app(BattlelogServer::class);
        $this->battlelog->setServer($this->server);

        $this->rconCache['squadNames'] = (json_decode(file_get_contents(resource_path('configs/battlefield/squadNames.json')),
            true))['squads'];

        $this->openConnection($debug);
        $this->getServerInfo();
        $this->getServerVersion();
    }

    /**
     * @inheritDoc
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * @param $rconPassword
     *
     * @return mixed|string
     */
    public function loginInsecure($rconPassword)
    {
        $loginStatus = $this->clientRequest(sprintf('login.plainText %s', $rconPassword))[0];

        if ($loginStatus == self::DEFAULT_GAME_SERVER_RESPONSE) {
            $this->isLoggedIn = true;

            return $loginStatus;
        }

        return self::LOGIN_FAILED;
    }

    /**
     * @param $rconPassword
     *
     * @return string
     */
    public function loginSecure($rconPassword)
    {
        $salt = $this->clientRequest('login.hashed')[1];
        $hashedPW = $this->_hex_str($salt) . $rconPassword;
        $saltedHashedPW = strtoupper(md5($hashedPW));

        $loginStatus = $this->clientRequest(sprintf('login.hashed %s', $saltedHashedPW))[0];

        if ($loginStatus == self::DEFAULT_GAME_SERVER_RESPONSE) {
            $this->isLoggedIn = true;

            return $loginStatus;
        }

        return self::LOGIN_FAILED;
    }

    /**
     * @return string
     */
    public function logout(): string
    {
        $this->isLoggedIn = false;

        return $this->clientRequest('logout')[0];
    }

    /**
     * @return string
     */
    public function quit(): string
    {
        return $this->clientRequest('quit')[0];
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->isLoggedIn;
    }

    /**
     * @return mixed
     */
    public function getServerInfo()
    {
        if (is_null($this->data['server'])) {
            $this->data['server'] = $this->clientRequest('serverinfo');
        }

        if (empty($this->data['battlelog'])) {
            $this->data['battlelog'] = $this->battlelog->getServerInfo();
        }

        return $this->data['server'];
    }

    /**
     * @return int
     */
    public function getServerVersion(): int
    {
        if (is_null($this->data['server_version'])) {
            $this->data['server_version'] = (int) $this->clientRequest('version')[2];
        }

        return $this->data['server_version'];
    }

    /**
     * @return string
     */
    public function getServerName(): string
    {
        return $this->getServerInfo()[1];
    }

    /**
     * @return int
     */
    public function getCurrentPlayers(): int
    {
        return $this->getServerInfo()[2];
    }

    /**
     * @return int
     */
    public function getMaxPlayers(): int
    {
        return $this->getServerInfo()[3];
    }

    /**
     * @param null :string $isGameType
     *
     * @return bool|string
     */
    public function getCurrentGameMode($isGameType = null)
    {
        if (! is_null($isGameType)) {
            return $this->getServerInfo()[4] == $isGameType;
        }

        return (string) $this->getServerInfo()[4];
    }

    /**
     * @return string
     */
    public function getCurrentMap(): string
    {
        return $this->getServerInfo()[5];
    }

    /**
     * @return int
     */
    public function getRoundsPlayed(): int
    {
        return $this->getServerInfo()[6];
    }

    /**
     * @return int
     */
    public function getTotalRounds(): int
    {
        return $this->getServerInfo()[7];
    }

    /**
     * @return array
     */
    public function getTeamScores(): array
    {
        switch ($this->getCurrentGameMode()) {
            case "CarrierAssaultSmall0":
            case "CarrierAssaultLarge0":
                // Leave defaults in place
                break;
            case "SquadObliteration0":
            case "Obliteration":
            case "SquadDeathmatch0":
                $this->data['team_scores']['team1'] = $this->getServerInfo()[9];
                $this->data['team_scores']['team2'] = $this->getServerInfo()[10];
                $this->data['team_scores']['team3'] = $this->getServerInfo()[11];
                $this->data['team_scores']['team4'] = $this->getServerInfo()[12];
                break;
            default:
                $this->data['team_scores']['team1'] = $this->getServerInfo()[9];
                $this->data['team_scores']['team2'] = $this->getServerInfo()[10];
        }

        return $this->data['team_scores'];
    }

    /**
     *
     */
    public function getServerUptime()
    {
        $serverInfo = $this->getServerInfo();
        $len = count($serverInfo);

        if ($this->server->game->Name == 'BF4') {
            switch ($this->getCurrentGameMode()) {
                case 'SquadDeathMatch0':
                case 'TeamDeathMatch0':

                    $ticketcap = $len < 28 ? null : intval($serverInfo[13]);
                    $uptime = $len < 28 ? (int) $serverInfo[14] : (int) $serverInfo[18];
                    $round = $len < 28 ? (int) $serverInfo[15] : (int) $serverInfo[19];
                    break;
                case 'CaptureTheFlag0':
                case 'Obliteration':
                case 'Chainlink0':
                case 'RushLarge0':
                case 'Domination0':
                case 'ConquestLarge0':
                case 'ConquestSmall0':
                    if ($this->getCurrentGameMode('CaptureTheFlag0')) {
                        $ticketcap = null;
                    } else {
                        $ticketcap = $len < 26 ? null : intval($serverInfo[11]);
                    }
                    $uptime = $len < 26 ? (int) $serverInfo[14] : (int) $serverInfo[16];
                    $round = $len < 26 ? (int) $serverInfo[15] : (int) $serverInfo[17];
                    break;
            }
        }
    }

    /**
     * @return array
     */
    public function processData()
    {
        $this->data['server_parsed'] = [
            'name'  => $this->getServerInfo()[1],
            'slots' => $this->battlelog->getOnlinePlayers(),
            'meta'  => [
                'extendedInfo' => $this->data['battlelog']['extendedInfo'],
                'is_ranked'    => (bool) $this->data['battlelog']['ranked'],
                'server'       => (new ServerResource($this->server)),
            ],
        ];

        return $this->data;
    }

    /**
     * Returns the game password. Not to be confused with the RCON password.
     *
     * @return string
     */
    public function adminVarGetGamepassword(): string
    {
        $response = $this->clientRequest('vars.gamePassword');

        switch ($response[0]) {
            case self::COMMAND_DISALLOWED:
                $response = 'Command not allowed on ranked servers.';
                break;
            case self::DEFAULT_GAME_SERVER_RESPONSE:
                $response = arrayToString($response);
                break;
        }

        return $response;
    }

    /**
     * Sets the game password. Not to be confused with the RCON password. Leave $gamePassword blank to reset.
     *
     * @param string $gamePassword
     *
     * @return string
     */
    public function adminVarSetGamepassword($gamePassword = ''): string
    {
        if ($this->checkPassword($gamePassword)) {
            $response = $this->clientRequest(sprintf('vars.gamePassword %s', $gamePassword));
        } else {
            $response = self::INVALID_PASSWORD;
        }

        switch ($response[0]) {
            case self::INVALID_ARGUMENTS:
                $response = self::INVALID_ARGUMENTS;
                break;
            case self::INVALID_PASSWORD:
                $response = 'Password does not conform to password format rules.';
                break;
            case self::INVALID_CONFIG:
                $response = 'Password can\'t be set if ranked is enabled.';
                break;
            case self::COMMAND_DISALLOWED:
                $response = 'Command not allowed on ranked servers.';
                break;
            case self::COMMAND_READ_ONLY:
                $response = 'Can\'t change server password through RCON command.';
                break;
            default:
                if (is_array($response)) {
                    $response = $response[0];
                }
        }

        return $response;
    }

    /**
     * Check if spotted targets are visible in the 3d-world.
     *
     * @return bool
     */
    public function adminVarGet3dSpotting(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.3dSpotting'));
    }

    /**
     * Set if spotted targets are visible in the 3d-world. Will take effect after map switch.
     *
     * @param $boolean
     *
     * @return string
     */
    public function adminVarSet3dSpotting($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.3dSpotting %s', booleanToString($boolean))), 0);
    }

    /**
     * Gets if players are allowed to switch to third-person vehicle cameras.
     *
     * @return bool
     */
    public function adminVarGet3pCam(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.3pCam'));
    }

    /**
     * Set if players should be allowed to switch to third-person vehicle cameras.
     *
     * @param $boolean
     *
     * @return string
     */
    public function adminVarSet3pCam($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.3pCam %s', booleanToString($boolean))), 0);
    }

    /**
     * Gets whether spectators are allowed to join without being on the spectator list.
     *
     * @return bool
     */
    public function adminVarGetAllowSpectators(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.alwaysAllowSpectators'));
    }

    /**
     * Set whether spectators are allowed to join without being on the spectator list.
     *
     * This command can only be used during startup.
     *
     * @param $boolean
     *
     * @return string
     */
    public function adminVarSetAllowSpectators($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.alwaysAllowSpectators %s', booleanToString($boolean))),
            0);
    }

    /**
     * Gets if the server should autobalance.
     *
     * @return bool
     */
    public function adminVarGetAutoBalance(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.autoBalance'));
    }

    /**
     * Set if the server should autobalance.
     *
     * @param $boolean
     *
     * @return string
     */
    public function adminVarSetAutoBalance($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.autoBalance %s', booleanToString($boolean))), 0);
    }

    public function adminVarGetBulletDamage(): int
    {
        return arrayToString($this->clientRequest('vars.bulletDamage'));
    }

    public function adminVarSetBulletDamage($modifier): int
    {
        return arrayToString($this->clientRequest(sprintf('vars.bulletDamage %s', $modifier)));
    }

    public function adminVarGetCommander()
    {
        return arrayToString($this->clientRequest('vars.commander'));
    }

    public function adminVarSetCommander($boolean)
    {
        return $this->clientRequest(sprintf('vars.commander %s', booleanToString($boolean)));
    }

    public function adminVarGetcrossHair()
    {
        return arrayToString($this->clientRequest('vars.crossHair'));
    }

    public function adminVarSetcrossHair($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.crossHair %s', booleanToString($boolean))));
    }

    public function adminVarGetForceReloadWholeMags()
    {
        return arrayToString($this->clientRequest('vars.forceReloadWholeMags'));
    }

    public function adminVarSetForceReloadWholeMags($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.forceReloadWholeMags %s', $boolean)));
    }

    public function adminVarGetFriendlyFire()
    {
        return arrayToString($this->clientRequest('vars.friendlyFire'));
    }

    public function adminVarSetFriendlyFire($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.friendlyFire %s', $boolean)));
    }

    public function adminVarGetGameModeCounter()
    {
        return arrayToString($this->clientRequest('vars.gameModeCounter'));
    }

    public function adminVarSetGameModeCounter($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.gameModeCounter %s', $integer)));
    }

    public function adminVarGetHitIndicators()
    {
        return arrayToString($this->clientRequest('vars.hitIndicatorsEnabled'));
    }

    public function adminVarSetHitIndicators($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.hitIndicatorsEnabled %s', $boolean)));
    }

    public function adminVarGetHud()
    {
        return arrayToString($this->clientRequest('vars.hud'));
    }

    public function adminVarSetHud($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.hud %s', $boolean)));
    }

    public function adminVarGetIdleBanRounds()
    {
        return arrayToString($this->clientRequest('vars.idleBanRounds'));
    }

    public function adminVarSetIdleBanRounds($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.idleBanRounds %s', $integer)));
    }

    public function adminVarGetIdleTimeout()
    {
        return arrayToString($this->clientRequest('vars.idleTimeout'));
    }

    public function adminVarSetIdleTimeout($seconds)
    {
        return arrayToString($this->clientRequest(sprintf('vars.idleTimeout %s', $seconds)));
    }

    public function adminVarGetKillCam()
    {
        return arrayToString($this->clientRequest('vars.killCam'));
    }

    public function adminVarSetKillCam($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.killCam %s', $boolean)));
    }

    public function adminVarGetMaxPlayers()
    {
        return arrayToString($this->clientRequest('vars.maxPlayers'));
    }

    public function adminVarSetMaxPlayers($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.maxPlayers %s', $integer)));
    }

    public function adminVarGetMaxSpectators()
    {
        return arrayToString($this->clientRequest('vars.maxSpectators'));
    }

    public function adminVarSetMaxSpectators($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.maxSpectators %s', $integer)));
    }

    public function adminVarGetMiniMap()
    {
        return arrayToString($this->clientRequest('vars.miniMap'));
    }

    public function adminVarSetMiniMap($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.miniMap %s', $boolean)));
    }

    public function adminVarGetMiniMapSpotting()
    {
        return arrayToString($this->clientRequest('vars.miniMapSpotting'));
    }

    public function adminVarSetMiniMapSpotting($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.miniMapSpotting %s', $boolean)));
    }

    public function adminVarGetNameTag()
    {
        return arrayToString($this->clientRequest('vars.nameTag'));
    }

    public function adminVarSetNameTag($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.nameTag %s', $boolean)));
    }

    public function adminVarGetOnlySquadLeaderSpawn()
    {
        return arrayToString($this->clientRequest('vars.onlySquadLeaderSpawn'));
    }

    public function adminVarSetOnlySquadLeaderSpawn($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.onlySquadLeaderSpawn %s', $boolean)));
    }

    public function adminVarGetPlayerRespawnTime()
    {
        return arrayToString($this->clientRequest('vars.playerRespawnTime'));
    }

    public function adminVarSetPlayerRespawnTime($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.playerRespawnTime %s', $integer)));
    }

    public function adminVarGetRegenerateHealth()
    {
        return arrayToString($this->clientRequest('vars.regenerateHealth'));
    }

    public function adminVarSetRegenerateHealth($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.regenerateHealth %s', $boolean)));
    }

    public function adminVarGetRoundLockdownCountdown()
    {
        return arrayToString($this->clientRequest('vars.roundLockdownCountdown'));
    }

    public function adminVarSetRoundLockdownCountdown($seconds)
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundLockdownCountdown %s', $seconds)));
    }

    public function adminVarGetRoundRestartPlayerCount()
    {
        return arrayToString($this->clientRequest('vars.roundRestartPlayerCount'));
    }

    public function adminVarSetRoundRestartPlayerCount($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundRestartPlayerCount %s', $integer)));
    }

    public function adminVarGetRoundStartPlayerCount()
    {
        return arrayToString($this->clientRequest('vars.roundStartPlayerCount'));
    }

    public function adminVarSetRoundStartPlayerCount($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundStartPlayerCount %s', $integer)));
    }

    public function adminVarGetRoundTimeLimit()
    {
        return arrayToString($this->clientRequest('vars.roundTimeLimit'));
    }

    public function adminVarSetRoundTimeLimit($percentage)
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundTimeLimit %s', $percentage)));
    }

    public function adminVarGetRoundWarmupTimeout()
    {
        return arrayToString($this->clientRequest('vars.roundWarmupTimeout'));
    }

    public function adminVarSetRoundWarmupTimeout($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundWarmupTimeout %s', $integer)));
    }

    public function adminVarGetServerDescription()
    {
        return arrayToString($this->clientRequest('vars.serverDescription'));
    }

    public function adminVarSetServerDescription($string)
    {
        return arrayToString($this->clientRequest(sprintf('vars.serverDescription %s', $string)));
    }

    public function adminVarGetServerMessage()
    {
        return arrayToString($this->clientRequest('vars.serverMessage'));
    }

    public function adminVarSetServerMessage($string)
    {
        return arrayToString($this->clientRequest(sprintf('vars.serverMessage %s', $string)));
    }

    public function adminVarGetServerName()
    {
        return arrayToString($this->clientRequest('vars.serverName'));
    }

    public function adminVarSetServerName($string)
    {
        return arrayToString($this->clientRequest(sprintf('vars.serverName %s', $string)));
    }

    public function adminVarGetSoldierHealth()
    {
        return arrayToString($this->clientRequest('vars.soldierHealth'));
    }

    public function adminVarSetSoldierHealth($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.soldierHealth %s', $integer)));
    }

    public function adminVarGetTeamKillKickForBan()
    {
        return arrayToString($this->clientRequest('vars.teamKillKickForBan'));
    }

    public function adminVarSetTeamKillKickForBan($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.teamKillKickForBan %s', $integer)));
    }

    public function adminVarGetTeamKillValueDecreasePerSecond()
    {
        return arrayToString($this->clientRequest('vars.teamKillValueDecreasePerSecond'));
    }

    public function adminVarSetTeamKillValueDecreasePerSecond($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.teamKillValueDecreasePerSecond %s', $integer)));
    }

    public function adminVarGetTeamKillCountForKick()
    {
        return arrayToString($this->clientRequest('vars.teamKillCountForKick'));
    }

    public function adminVarSetTeamKillCountForKick($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.teamKillCountForKick %s', $integer)));
    }

    public function adminVarGetTeamKillValueForKick()
    {
        return arrayToString($this->clientRequest('vars.teamKillCountForKick'));
    }

    public function adminVarSetTeamKillValueForKick($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.teamKillCountForKick %s', $integer)));
    }

    public function adminVarGetVehicleSpawnAllowed()
    {
        return arrayToString($this->clientRequest('vars.vehicleSpawnAllowed'));
    }

    public function adminVarSetVehicleSpawnAllowed($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.vehicleSpawnAllowed %s', $boolean)));
    }

    public function adminVarGetVehicleSpawnDelay()
    {
        return arrayToString($this->clientRequest('vars.vehicleSpawnDelay'));
    }

    public function adminVarSetVehicleSpawnDelay($integer)
    {
        return arrayToString($this->clientRequest(sprintf('vars.vehicleSpawnDelay %s', $integer)));
    }

    public function adminKickPlayer($player, $reason = 'Kicked by administrator')
    {
        $response = $this->clientRequest(sprintf('admin.kickPlayer %s %s', $player, $reason));

        switch ($response[0]) {
            case self::INVALID_ARGUMENTS:
                $response = 'Invalid arguments provided.';
                break;
            case self::DEFAULT_GAME_SERVER_RESPONSE:
                $response = sprintf('%s was kicked from the server with the reason "%s"', $player, $reason);
                break;
            case self::PLAYER_NOT_FOUND:
                $response = sprintf('%s was not found on the server.', $player);
                break;
            default:
                $response = $response[0];
        }

        return $response;
    }

    public function adminKillPlayer($player, $reason = 'Killed by administrator')
    {
        $response = $this->clientRequest(sprintf('admin.killPlayer %s', $player));

        switch ($response[0]) {
            case self::INVALID_ARGUMENTS:
                $response = 'Invalid arguments provided.';
                break;
            case self::DEFAULT_GAME_SERVER_RESPONSE:
                $response = sprintf('%s was killed with the reason "%s"', $player, $reason);
                break;
            case self::PLAYER_NOT_FOUND:
                $response = sprintf('%s was not found on the server.', $player);
                break;
            default:
                $response = $response[0];
        }

        return $response;
    }

    /**
     * @return array
     */
    public function listPlayers(): array
    {
        return $this->tabulate($this->clientRequest('admin.listplayers all'));
    }

    /**
     * @param $id
     *
     * @return string
     */
    protected function getSquadName($id): string
    {
        return $this->rconCache['squadNames'][$id];
    }

    /**
     * @param $res
     *
     * @return array
     */
    private function tabulate($res): array
    {
        array_shift($res);

        $nColumns = $res[0];

        $columns = [];

        for ($i = 1; $i <= $nColumns; $i++) {
            $columns[] = $res[$i];
        }

        if ($this->getCurrentGame() == 'BF3') {
            $nRows = $res[10];
        } else {
            $nRows = $res[11];
        }

        $rows = [
            'players' => [],
            'columns' => [],
        ];

        for ($n = 0; $n < $nRows; $n++) {
            $row = [];

            for ($j = 0; $j < count($columns); $j++) {
                $value = $res[++$i];
                $column = $columns[$j];
                $row[$column] = is_numeric($value) ? (int) $value : $value;
            }

            $row['meta']['squadName'] = $this->getSquadName($row['squadId']);

            $rows['players'][] = $row;
        }

        $rows['columns'] = $columns;

        return $rows;
    }

    /**
     * @return string
     */
    private function getCurrentGame(): string
    {
        return $this->server->game->Name;
    }

    /**
     * @param bool $debug
     *
     * @throws \Throwable
     */
    private function openConnection($debug = false)
    {
        try {
            $this->checkFuncs('socket_create', 'socket_connect', 'socket_strerror', 'socket_last_error',
                'socket_set_block', 'socket_read', 'socket_write', 'socket_close');

            $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if (function_exists('socket_set_option')) {
                socket_set_option($this->sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
                socket_set_option($this->sock, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
            }

            $this->connection = socket_connect($this->sock, $this->server->ip, $this->server->port);

            if ($debug) {
                Log::debug(socket_strerror(socket_last_error()));
            }

            if ($this->connection) {
                socket_set_block($this->sock);
            }

            $this->sockType = 1;
        } catch (RconException $e) {
            Log::error($e->getMessage());
            try {
                $this->checkFuncs('fsockopen');

                $this->sock = fsockopen('tcp://' . $this->server->ip, $this->server->port, $errno, $errstr, 5);

                if ($debug) {
                    Log::debug(sprintf('%s - %s', $errno, $errstr));
                }

                $this->connection = $this->sock;
                $this->sockType = 2;
            } catch (RconException $e) {
                Log::error($e->getMessage());
            }
        }
    }

    /**
     * Checks a list of function(s) to see if they exist otherwise it will throw an exception.
     *
     * @return bool
     * @throws \Throwable
     */
    private function checkFuncs()
    {
        $args = func_get_args();

        foreach ($args as $arg) {
            throw_unless(function_exists($arg), new RconException(sprintf('Function "%s" does not exist.', $arg)));
        }

        return true;
    }

    /**
     * @return $this
     */
    private function closeConnection()
    {
        if ($this->sockType == 1) {
            socket_close($this->sock);
        } else {
            fclose($this->sock);
        }

        $this->_sockType = null;
        $this->connection = false;

        return $this;
    }

    /**
     * @param $clientRequest
     *
     * @return mixed
     */
    private function clientRequest($clientRequest)
    {
        $data = $this->_encodeClientRequest($clientRequest);

        if ($this->sockType == 1) {
            socket_write($this->sock, $data, strlen($data));
        } else {
            fwrite($this->sock, $data, strlen($data));
        }

        $receiveBuffer = '';
        list($packet, $receiveBuffer) = $this->_receivePacket($receiveBuffer);
        list($isFromServer, $isResponse, $sequence, $requestAnswer) = $this->_decodePacket($packet);

        return $requestAnswer;
    }

    /**
     * @param $data
     *
     * @return string
     */
    private function _encodeClientRequest($data)
    {
        $packet = $this->_encodePacket(false, false, $this->clientSequenceNr, $data);
        $this->_clientSequenceNr = ($this->clientSequenceNr + 1) & 0x3fffffff;

        return $packet;
    }

    /**
     * @param $isFromServer
     * @param $isResponse
     * @param $sequence
     * @param $data
     *
     * @return string
     */
    private function _encodePacket($isFromServer, $isResponse, $sequence, $data)
    {
        $data = explode(' ', $data);
        if ($data[0] == 'admin.yell' && isset($data[1])) {
            $adminYell = [$data[0], '', '', ''];

            $yellStyle = '';
            $yellKey = 0;
            foreach ($data as $key => $content) {
                if ($key != 0) {
                    if ($content == '{%player%}') {
                        $yellStyle = 'player';
                        $yellKey = $key;
                        break;
                    } else {
                        if ($content == '{%team%}') {
                            $yellStyle = 'team';
                            $yellKey = $key;
                            break;
                        } else {
                            if ($content == '{%all%}') {
                                $yellStyle = 'all';
                                $yellKey = $key;
                                break;
                            }
                        }
                    }
                }
            }

            if ($yellStyle == 'all') {
                foreach ($data as $key => $content) {
                    if ($key != 0 && $key < $yellKey - 1) {
                        $adminYell[1] .= $content . ' ';
                    } else {
                        if ($key == $yellKey) {
                            $adminYell[3] = $yellStyle;
                        } else {
                            if ($key == $yellKey - 1) {
                                $adminYell[2] = $data[$yellKey - 1];
                            }
                        }
                    }
                }

                $adminYell[1] = trim($adminYell[1]);
            } else {
                if ($yellStyle == 'player' || $yellStyle == 'team') {
                    $adminYell[4] = '';

                    foreach ($data as $key => $content) {
                        if ($key != 0 && $key < $yellKey - 1) {
                            $adminYell[1] .= $content . ' ';
                        } else {
                            if ($key == $yellKey) {
                                $adminYell[3] = $yellStyle;
                            } else {
                                if ($key == $yellKey - 1) {
                                    $adminYell[2] = $data[$yellKey - 1];
                                } else {
                                    if ($key > $yellKey) {
                                        $adminYell[4] .= $content . ' ';
                                    }
                                }
                            }
                        }
                    }

                    $adminYell[4] = trim($adminYell[4]); // trim whitespaces
                }
            }

            $data = $adminYell;
        } else {
            if ($data[0] == 'vars.serverDescription' && isset($data[1])) {
                $serverDesc = [$data[0], ''];
                foreach ($data as $key => $value) {
                    if ($key != 0) {
                        $serverDesc[1] .= $value . ' ';
                    }
                }
                $serverDesc[1] = trim($serverDesc[1]);

                $data = $serverDesc;
            } else {
                if ($data[0] == 'admin.kickPlayer' && isset($data[1])) {
                    $reason = false;
                    foreach ($data as $key => $value) {
                        if ($value == '{%reason%}') {
                            $reason = true;
                        }
                    }

                    if (! $reason) {
                        $kickPlayer = [$data[0], ''];
                        foreach ($data as $key => $value) {
                            if ($key != 0) {
                                $kickPlayer[1] .= $value . ' ';
                            }
                        }
                        $kickPlayer[1] = trim($kickPlayer[1]);
                    } else {
                        $kickPlayer = [$data[0], '', ''];
                        $i = 0;
                        foreach ($data as $key => $value) {
                            if ($key != 0) {
                                if ($value == '{%reason%}') {
                                    $i = $key;
                                }

                                if ($i == 0) {
                                    $kickPlayer[1] .= $value . ' ';
                                } else {
                                    if ($key != $i) {
                                        $kickPlayer[2] .= $value . ' ';
                                    }
                                }
                            }
                        }
                        $kickPlayer[1] = trim($kickPlayer[1]); // trim whitespaces
                        $kickPlayer[2] = trim($kickPlayer[2]); // trim whitespaces
                    }

                    $data = $kickPlayer;
                } else {
                    if ($data[0] == 'banList.add' || $data[0] == 'banList.remove' && isset($data[1])) {
                        $dataCount = count($data) - 1;
                        $banPlayer = [$data[0], $data[1], ''];
                        foreach ($data as $key => $value) {
                            if ($key != 0 && $key != 1) {
                                if ($data[0] == 'banList.add' && $key != $dataCount) {
                                    $banPlayer[2] .= $value . ' ';
                                } else {
                                    if ($data[0] == 'banList.remove') {
                                        $banPlayer[2] .= $value . ' ';
                                    }
                                }
                            }
                        }

                        $banPlayer[2] = trim($banPlayer[2]); // trim whitespace

                        if ($data[0] == 'banList.add') {
                            $banPlayer[3] = $data[$dataCount];
                        }

                        $data = $banPlayer;
                    } else {
                        if ($data[0] == 'admin.listPlayers' || $data[0] == 'listPlayers' && isset($data[1])) {
                            $listPlayer = [$data[0]];
                            if ($data[1] != 'all') {
                                if ($data[1] == 'player') {
                                    $listPlayer[1] = $data[1];
                                    $listPlayer[2] = '';
                                    foreach ($data as $key => $value) {
                                        if ($key != 0 && $key != 1) {
                                            $listPlayer[2] .= $value . ' ';
                                        }
                                    }

                                    $listPlayer[2] = trim($listPlayer[2]); // trim ending whitespace
                                }
                                if ($data[1] == 'team') {
                                    $listPlayer[1] = $data[1];
                                    $listPlayer[2] = '';
                                    foreach ($data as $key => $value) {
                                        if ($key != 0 && $key != 1) {
                                            $listPlayer[2] .= $value . ' ';
                                        }
                                    }

                                    $listPlayer[2] = trim($listPlayer[2]); // trim ending whitespace
                                }
                            } else {
                                $listPlayer[1] = $data[1];
                            }

                            $data = $listPlayer;
                        } else {
                            if ($data[0] == 'reservedSlots.addPlayer' || $data[0] == 'reservedSlots.removePlayer' && isset($data[1])) {
                                $reservedSlots = [$data[0], ''];
                                foreach ($data as $key => $value) {
                                    if ($key != 0) {
                                        $reservedSlots[1] .= $value . ' ';
                                    }
                                }

                                $reservedSlots[1] = trim($reservedSlots[1]); // trim whitespace

                                $data = $reservedSlots;
                            } else {
                                if ($data[0] == 'admin.say' && isset($data[1])) {
                                    $adminSay = [$data[0], '', '', ''];
                                    $i = 0;
                                    foreach ($data as $key => $value) {
                                        if ($key != 0) {
                                            if ($value == '{%player%}' || $value == '{%team%}' || $value == '{%all%}') {
                                                $i = $key;
                                                $adminSay[2] = preg_replace('/[{}%]/', '', $value);
                                            }
                                            if ($i == 0) {
                                                $adminSay[1] .= $value . ' ';
                                            } else {
                                                if ($key != $i && $adminSay[2] != 'all') {
                                                    $adminSay[3] .= $value . ' ';
                                                }
                                            }
                                        }
                                    }

                                    $adminSay[1] = trim($adminSay[1]); // trim whitespace
                                    $adminSay[3] = trim($adminSay[3]); // trim whitespace

                                    if ($adminSay[2] == 'all') {
                                        unset($adminSay[3]);
                                    }

                                    $data = $adminSay;
                                } else {
                                    if ($data[0] == 'admin.killPlayer' && isset($data[1])) {
                                        $adminKillPlayer = [$data[0], ''];
                                        $i = 0;
                                        foreach ($data as $key => $value) {
                                            if ($key != 0) {
                                                $adminKillPlayer[1] .= $value . ' ';
                                            }
                                        }

                                        $adminKillPlayer[1] = trim($adminKillPlayer[1]); // trim whitespace

                                        $data = $adminKillPlayer;
                                    } else {
                                        if ($data[0] == 'admin.movePlayer' && isset($data[1])) {
                                            $dataCount = count($data) - 3;
                                            $adminMovePlayer = [$data[0], '', '', '', ''];
                                            $i = 0;
                                            foreach ($data as $key => $value) {
                                                if ($key != 0 && $key < $dataCount) {
                                                    $adminMovePlayer[1] .= $value . ' ';
                                                }
                                            }

                                            $adminMovePlayer[1] = trim($adminMovePlayer[1]); // trim whitespace
                                            $adminMovePlayer[2] = $data[$dataCount];
                                            $adminMovePlayer[3] = $data[$dataCount + 1];
                                            $adminMovePlayer[4] = $data[$dataCount + 2];

                                            $data = $adminMovePlayer;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $encodedHeader = $this->_encodeHeader($isFromServer, $isResponse, $sequence);
        $encodedNumWords = $this->_encodeInt32(count($data));
        list($wordsSize, $encodedWords) = $this->_encodeWords($data);
        $encodedSize = $this->_encodeInt32($wordsSize + 12);

        return $encodedHeader . $encodedSize . $encodedNumWords . $encodedWords;
    }

    /**
     * @param $isFromServer
     * @param $isResponse
     * @param $sequence
     *
     * @return string
     */
    private function _encodeHeader($isFromServer, $isResponse, $sequence)
    {
        $header = $sequence & 0x3fffffff;
        if ($isFromServer) {
            $header += 0x80000000;
        }
        if ($isResponse) {
            $header += 0x40000000;
        }

        return pack('I', $header);
    }

    /**
     * @param $size
     *
     * @return string
     */
    private function _encodeInt32($size)
    {
        return pack('I', $size);
    }

    /**
     * @param $words
     *
     * @return array
     */
    private function _encodeWords($words)
    {
        $size = 0;
        $encodedWords = '';
        foreach ($words as $word) {
            $strWord = $word;
            $encodedWords .= $this->_encodeInt32(strlen($strWord));
            $encodedWords .= $strWord;
            $encodedWords .= "\x00";
            $size += strlen($strWord) + 5;
        }

        return [
            $size,
            $encodedWords,
        ];
    }

    /**
     * @param $receiveBuffer
     *
     * @return array
     * @throws RconException
     */
    private function _receivePacket($receiveBuffer)
    {
        $errMsg = sprintf('Could not read packet data from game server %s (%s).', $this->server->ServerName,
            $this->server->ip);
        $count = 0;
        while (! $this->_containsCompletePacket($receiveBuffer)) {
            if ($this->sockType == 1) {
                $socketbuffer = @socket_read($this->sock, 4096);
                if ($socketbuffer == false) {
                    Log::error($errMsg);
                    throw new RconException('Could not read packet data from game server.');
                }
                $receiveBuffer .= $socketbuffer;
            } else {
                $socketbuffer = fread($this->sock, 4096);
                if ($socketbuffer == false) {
                    Log::error($errMsg);
                    throw new RconException('Could not read packet data from game server.');
                }
                $receiveBuffer .= $socketbuffer;
            }
        }

        $packetSize = $this->_decodeInt32(substr($receiveBuffer, 4, 4));

        $packet = substr($receiveBuffer, 0, $packetSize);
        $receiveBuffer = substr($receiveBuffer, $packetSize, strlen($receiveBuffer));

        return [
            $packet,
            $receiveBuffer,
        ];
    }

    /**
     * @param $data
     *
     * @return bool
     */
    private function _containsCompletePacket($data)
    {
        if (strlen($data) < 8) {
            return false;
        }

        if (strlen($data) < $this->_decodeInt32(substr($data, 4, 4))) {
            return false;
        }

        return true;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    private function _decodeInt32($data)
    {
        $decode = unpack('I', $data);

        return $decode[1];
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function _decodePacket($data)
    {
        list($isFromServer, $isResponse, $sequence) = $this->_decodeHeader($data);
        $wordsSize = $this->_decodeInt32(substr($data, 4, 4)) - 12;
        $words = $this->_decodeWords($wordsSize, substr($data, 12));

        return [
            $isFromServer,
            $isResponse,
            $sequence,
            $words,
        ];
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function _decodeHeader($data)
    {
        $header = unpack('I', $data);

        return [
            $header & 0x80000000,
            $header & 0x40000000,
            $header & 0x3fffffff,
        ];
    }

    /**
     * @param $size
     * @param $data
     *
     * @return array
     */
    private function _decodeWords($size, $data)
    {
        $numWords = $this->_decodeInt32($data);
        $words = [];
        $offset = 0;
        while ($offset < $size) {
            $wordLen = $this->_decodeInt32(substr($data, $offset, 4));
            $word = substr($data, $offset + 4, $wordLen);
            $words[] = $word;
            $offset += $wordLen + 5;
        }

        return $words;
    }

    /**
     * @param $hex
     *
     * @return string
     */
    private function _hex_str($hex)
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }

        return $string;
    }

    /**
     * A password is from 0 up to 16 characters in length, inclusive.
     *
     * @param $password
     *
     * @return bool
     */
    private function checkPassword($password)
    {
        return preg_match('/^[a-zA-Z0-9]{0,16}+$/', $password) === 1;
    }
}
