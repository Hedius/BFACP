<?php

namespace BFACP\Libraries;


use BFACP\Exceptions\Adkats\BattlelogException;
use BFACP\Exceptions\Adkats\RconException;
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

    /**
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
     * Battlefield 3
     */
    const BF3 = 'BF3';

    /**
     * Battlefield 4
     */
    const BF4 = 'BF4';

    /**
     * Battlefield: Bad Company 2
     */
    const BC2 = 'BFBC2';

    /**
     * Battlefield Hardline
     */
    const BFH = 'BFHL';

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
        throw_unless($server->ServerID, new RconException('Can\'t load requested server due to database error.'));

        $this->server = $server;

        if ($this->getCurrentGame() != self::BC2) {
            $this->battlelog = app(BattlelogServer::class);
            $this->battlelog->setServer($this->server);
        }

        $this->loadConfigs();
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
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function loginInsecure($rconPassword)
    {
        $loginStatus = $this->clientRequest(sprintf('login.plainText %s', $rconPassword))[0];

        return $this->setLoginStatus($loginStatus);
    }

    /**
     * @param $rconPassword
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function loginSecure($rconPassword)
    {
        $salt = $this->clientRequest('login.hashed')[1];
        $hashedPW = $this->_hex_str($salt) . $rconPassword;
        $saltedHashedPW = strtoupper(md5($hashedPW));

        $loginStatus = $this->clientRequest(sprintf('login.hashed %s', $saltedHashedPW))[0];

        return $this->setLoginStatus($loginStatus);
    }

    /**
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function logout(): string
    {
        $this->isLoggedIn = false;

        return $this->clientRequest('logout')[0];
    }

    /**
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
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
     * @return array
     * @throws \Throwable
     */
    public function getServerInfo(): array
    {
        if (is_null($this->data['server'])) {
            $command = in_array($this->getCurrentGame(), [self::BC2, self::BF3]) ? 'serverInfo' : 'serverinfo';

            $req = $this->clientRequest($command);
            array_shift($req);

            $this->data['server'] = $req;
        }

        try {
            if (! is_null($this->battlelog) && empty($this->data['battlelog'])) {
                $this->data['battlelog'] = $this->battlelog->getServerInfo();
            }
        } catch (BattlelogException $e) {
            // Catch battlelog exception
        }

        return $this->data['server'];
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function getServerVersion(): int
    {
        if (is_null($this->data['server_version'])) {
            $this->data['server_version'] = arrayToInteger($this->clientRequest('version'), 2);
        }

        return $this->data['server_version'];
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function getServerName(): string
    {
        return arrayToString($this->getServerInfo(), 0);
    }

    /**
     * @return int
     * @throws \Throwable
     */
    public function getCurrentPlayers(): int
    {
        return arrayToInteger($this->getServerInfo(), 1);
    }

    /**
     * @return int
     * @throws \Throwable
     */
    public function getMaxPlayers(): int
    {
        return arrayToInteger($this->getServerInfo(), 2);
    }

    /**
     * @param      null :string $isGameType
     *
     * @param bool $readable
     *
     * @return bool|string
     * @throws \Throwable
     */
    public function getCurrentGameMode($isGameType = null, $readable = true)
    {
        $gametype = arrayToString($this->getServerInfo(), 3);
        if (! is_null($isGameType)) {
            return $gametype == $isGameType;
        }

        return $readable ? $this->rconCache['modes'][$gametype] : $gametype;
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function getCurrentMap(): string
    {
        $mapCode = arrayToString($this->getServerInfo(), 4);

        return $this->rconCache['maps'][$mapCode];
    }

    /**
     * @return int
     * @throws \Throwable
     */
    public function getRoundsPlayed(): int
    {
        return arrayToInteger($this->getServerInfo(), 5);
    }

    /**
     * @return int
     * @throws \Throwable
     */
    public function getTotalRounds(): int
    {
        return arrayToInteger($this->getServerInfo(), 6);
    }

    /**
     * @return array
     * @throws \Throwable
     */
    public function getTeamScores(): array
    {
        switch ($this->getCurrentGame()) {
            case self::BF4:
                switch ($this->getCurrentGameMode()) {
                    case "CarrierAssaultSmall0":
                    case "CarrierAssaultLarge0":
                        // Leave defaults in place
                        break;
                    case "SquadObliteration0":
                    case "SquadDeathmatch0":
                        $this->data['team_scores']['team1'] = (int) $this->getServerInfo()[8];
                        $this->data['team_scores']['team2'] = (int) $this->getServerInfo()[9];
                        $this->data['team_scores']['team3'] = (int) $this->getServerInfo()[10];
                        $this->data['team_scores']['team4'] = (int) $this->getServerInfo()[11];
                        break;
                    default:
                        $this->data['team_scores']['team1'] = (int) $this->getServerInfo()[8];
                        $this->data['team_scores']['team2'] = (int) $this->getServerInfo()[9];
                }
                break;
        }

        return $this->data['team_scores'];
    }

    /**
     *
     */
    public function getServerUptime()
    {
        $serverInfo = $this->getServerInfo();
        //$len = count($serverInfo);

        $uptime = null;

        switch ($this->getCurrentGame()) {
            case self::BF4:
                switch ($this->getCurrentGameMode(null, false)) {
                    case 'CaptureTheFlag0':
                    case 'Obliteration':
                    case 'Chainlink0':
                    case 'RushLarge0':
                    case 'Domination0':
                    case 'ConquestLarge0':
                    case 'ConquestSmall0':
                        $uptime = (int) arrayToString($serverInfo, 15);
                        break;
                    case 'SquadDeathMatch0':
                    case 'TeamDeathMatch0':
                        $uptime = (int) arrayToString($serverInfo, 13);
                        break;
                }
                break;
        }

        return $uptime;
    }

    /**
     * @return BattlelogServer
     */
    public function getBattlelog(): BattlelogServer
    {
        return $this->battlelog;
    }

    /**
     * Returns the game password. Not to be confused with the RCON password.
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
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
     * @throws \BFACP\Exceptions\Adkats\RconException
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
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
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
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSet3dSpotting($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.3dSpotting %s', booleanToString($boolean))), 0);
    }

    /**
     * Gets if players are allowed to switch to third-person vehicle cameras.
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
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
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSet3pCam($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.3pCam %s', booleanToString($boolean))), 0);
    }

    /**
     * Gets whether spectators are allowed to join without being on the spectator list.
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
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
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetAllowSpectators($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.alwaysAllowSpectators %s', booleanToString($boolean))),
            0);
    }

    /**
     * Gets if the server should autobalance.
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
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
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetAutoBalance($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.autoBalance %s', booleanToString($boolean))), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetBulletDamage(): int
    {
        return arrayToString($this->clientRequest('vars.bulletDamage'));
    }

    /**
     * @param $modifier
     *
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetBulletDamage($modifier): int
    {
        return arrayToString($this->clientRequest(sprintf('vars.bulletDamage %s', $modifier)));
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetCommander(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.commander'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetCommander($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.commander %s', booleanToString($boolean))), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetForceReloadWholeMags(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.forceReloadWholeMags'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetForceReloadWholeMags($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.forceReloadWholeMags %s', booleanToString($boolean))),
            0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetFriendlyFire(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.friendlyFire'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetFriendlyFire($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.friendlyFire %s', booleanToString($boolean))), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetGameModeCounter(): int
    {
        return arrayToInteger($this->clientRequest('vars.gameModeCounter'));
    }

    /**
     * Set scale factor for number of tickets to end round, in percent
     *
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetGameModeCounter($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.gameModeCounter %s', $integer)), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetHitIndicators(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.hitIndicatorsEnabled'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetHitIndicators($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.hitIndicatorsEnabled %s', booleanToString($boolean))),
            0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetHud(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.hud'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetHud($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.hud %s', booleanToString($boolean))), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetIdleBanRounds(): int
    {
        return arrayToInteger($this->clientRequest('vars.idleBanRounds'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetIdleBanRounds($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.idleBanRounds %s', $integer)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetIdleTimeout(): int
    {
        return arrayToInteger($this->clientRequest('vars.idleTimeout'));
    }

    /**
     * @param $seconds
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetIdleTimeout($seconds): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.idleTimeout %s', $seconds)), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetKillCam(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.killCam'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetKillCam($boolean)
    {
        return arrayToString($this->clientRequest(sprintf('vars.killCam %s', booleanToString($boolean))), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetMaxPlayers(): int
    {
        return arrayToInteger($this->clientRequest('vars.maxPlayers'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetMaxPlayers($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.maxPlayers %s', $integer)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetMaxSpectators(): int
    {
        return arrayToInteger($this->clientRequest('vars.maxSpectators'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetMaxSpectators($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.maxSpectators %s', $integer)), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetMiniMap(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.miniMap'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetMiniMap($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.miniMap %s', booleanToString($boolean))), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetMiniMapSpotting(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.miniMapSpotting'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetMiniMapSpotting($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.miniMapSpotting %s', booleanToString($boolean))), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetNameTag(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.nameTag'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetNameTag($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.nameTag %s', booleanToString($boolean))), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetOnlySquadLeaderSpawn(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.onlySquadLeaderSpawn'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetOnlySquadLeaderSpawn($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.onlySquadLeaderSpawn %s', booleanToString($boolean))),
            0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetPlayerRespawnTime(): int
    {
        return arrayToInteger($this->clientRequest('vars.playerRespawnTime'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetPlayerRespawnTime($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.playerRespawnTime %s', $integer)), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetRegenerateHealth(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.regenerateHealth'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetRegenerateHealth($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.regenerateHealth %s', booleanToString($boolean))), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetRoundLockdownCountdown(): int
    {
        return arrayToInteger($this->clientRequest('vars.roundLockdownCountdown'));
    }

    /**
     * @param $seconds
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetRoundLockdownCountdown($seconds): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundLockdownCountdown %s', $seconds)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetRoundRestartPlayerCount(): int
    {
        return arrayToInteger($this->clientRequest('vars.roundRestartPlayerCount'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetRoundRestartPlayerCount($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundRestartPlayerCount %s', $integer)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetRoundStartPlayerCount(): int
    {
        return arrayToInteger($this->clientRequest('vars.roundStartPlayerCount'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetRoundStartPlayerCount($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundStartPlayerCount %s', $integer)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetRoundTimeLimit(): int
    {
        return arrayToInteger($this->clientRequest('vars.roundTimeLimit'));
    }

    /**
     * @param $percentage
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetRoundTimeLimit($percentage): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundTimeLimit %s', $percentage)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetRoundWarmupTimeout(): int
    {
        return arrayToInteger($this->clientRequest('vars.roundWarmupTimeout'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetRoundWarmupTimeout($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.roundWarmupTimeout %s', $integer)), 0);
    }

    /**
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetServerDescription(): string
    {
        return arrayToString($this->clientRequest('vars.serverDescription'));
    }

    /**
     * @param $string
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetServerDescription($string): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.serverDescription %s', $string)), 0);
    }

    /**
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetServerMessage(): string
    {
        return arrayToString($this->clientRequest('vars.serverMessage'));
    }

    /**
     * @param $string
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetServerMessage($string): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.serverMessage %s', $string)), 0);
    }

    /**
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetServerName(): string
    {
        return arrayToString($this->clientRequest('vars.serverName'));
    }

    /**
     * @param $string
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetServerName($string): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.serverName %s', $string)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetSoldierHealth(): int
    {
        return arrayToInteger($this->clientRequest('vars.soldierHealth'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetSoldierHealth($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.soldierHealth %s', $integer)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetTeamKillKickForBan(): int
    {
        return arrayToInteger($this->clientRequest('vars.teamKillKickForBan'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetTeamKillKickForBan($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.teamKillKickForBan %s', $integer)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetTeamKillValueDecreasePerSecond(): int
    {
        return arrayToInteger($this->clientRequest('vars.teamKillValueDecreasePerSecond'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetTeamKillValueDecreasePerSecond($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.teamKillValueDecreasePerSecond %s', $integer)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetTeamKillCountForKick(): int
    {
        return arrayToInteger($this->clientRequest('vars.teamKillCountForKick'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetTeamKillCountForKick($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.teamKillCountForKick %s', $integer)), 0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetTeamKillValueForKick(): int
    {
        return arrayToInteger($this->clientRequest('vars.teamKillCountForKick'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetTeamKillValueForKick($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.teamKillCountForKick %s', $integer)), 0);
    }

    /**
     * @return bool
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetVehicleSpawnAllowed(): bool
    {
        return arrayToBoolean($this->clientRequest('vars.vehicleSpawnAllowed'));
    }

    /**
     * @param $boolean
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetVehicleSpawnAllowed($boolean): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.vehicleSpawnAllowed %s', booleanToString($boolean))),
            0);
    }

    /**
     * @return int
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetVehicleSpawnDelay(): int
    {
        return arrayToInteger($this->clientRequest('vars.vehicleSpawnDelay'));
    }

    /**
     * @param $integer
     *
     * @return string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarSetVehicleSpawnDelay($integer): string
    {
        return arrayToString($this->clientRequest(sprintf('vars.vehicleSpawnDelay %s', $integer)), 0);
    }

    /**
     * @param        $player
     * @param string $reason
     *
     * @return mixed|string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
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

    /**
     * @param        $player
     * @param string $reason
     *
     * @return mixed|string
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
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
     * Subset can be one of the following.
     *
     * all - all players on the server
     * team <team number: integer> - all players in the specified team
     * squad <team number: integer> <squad number: integer> - all players in the specified team+squad
     * player <player name: string> - one specific player
     *
     * @param string $subset
     *
     * @param array  $prams
     *
     * @return array
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function listPlayers($subset = 'all', $prams = []): array
    {
        $command = in_array($this->getCurrentGame(), [self::BC2, self::BF3]) ? sprintf('admin.listPlayers %s',
            $subset) : sprintf('admin.listplayers %s', $subset);

        if (! empty($prams)) {
            foreach ($prams as $pram) {
                $command .= ' ' . $pram;
            }
        }

        return $this->tabulate($this->clientRequest($command));
    }

    /**
     * @return \BFACP\Libraries\BattlefieldConn
     * @throws \BFACP\Exceptions\Adkats\RconException
     */
    public function adminVarGetTeamFactions(): BattlefieldConn
    {
        $factionOverrides = $this->clientRequest('vars.teamFactionOverride');
        array_shift($factionOverrides);

        // Neutral;
        $this->rconCache['factions'][0] = $this->rconCache['teams'][0];

        foreach ($factionOverrides as $key => $team) {
            $this->rconCache['factions'][$key + 1] = $this->rconCache['teams'][$team + 1];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentGame(): string
    {
        return $this->server->game->Name;
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
     * @param $id
     *
     * @return string
     */
    protected function getTeamName($id)
    {
        if ($this->getCurrentGame() == self::BF4) {
            return $this->rconCache['factions'][$id];
        }

        return $this->rconCache['teams'][$id];
    }

    /**
     * @param $id
     *
     * @return string
     */
    protected function getPlayerType($id): string
    {
        switch ($id) {
            case 0:
                $type = 'Player';
                break;
            case 1:
                $type = 'Spectator';
                break;
            case 2:
            case 3:
                $type = 'Commander';
                break;
            default:
                $type = 'Unknown';
        }

        return $type;
    }

    /**
     * @param $connected
     *
     * @return string
     */
    private function setLoginStatus($connected)
    {
        if ($connected == self::DEFAULT_GAME_SERVER_RESPONSE) {
            $this->isLoggedIn = true;

            if ($this->getCurrentGame() == self::BF4) {
                $this->adminVarGetTeamFactions();
            }

            return $connected;
        }

        return self::LOGIN_FAILED;
    }

    /**
     * @return $this
     */
    private function loadConfigs()
    {
        $squadsPath = resource_path('configs/battlefield/squadNames.json');
        $mapsPath = resource_path(sprintf('configs/battlefield/%s/maps.json', $this->getCurrentGame()));
        $modesPath = resource_path(sprintf('configs/battlefield/%s/modes.json', $this->getCurrentGame()));
        $teamsPath = resource_path(sprintf('configs/battlefield/%s/teams.json', $this->getCurrentGame()));

        $this->rconCache['maps'] = json_decode(file_get_contents($mapsPath), true);
        $this->rconCache['modes'] = json_decode(file_get_contents($modesPath), true);
        $this->rconCache['teams'] = json_decode(file_get_contents($teamsPath), true);
        $this->rconCache['squadNames'] = json_decode(file_get_contents($squadsPath), true)['squads'];

        return $this;
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

        switch ($this->getCurrentGame()) {
            case self::BF3:
            case self::BC2:
                $nRows = $res[10];
                break;
            default:
                $nRows = $res[11];
        }

        $rows = [
            'players'    => [],
            'spectators' => [],
            'commanders' => [],
            'columns'    => [],
        ];

        for ($n = 0; $n < $nRows; $n++) {
            $row = [];

            for ($j = 0; $j < count($columns); $j++) {
                $value = $res[++$i];
                $column = $columns[$j];
                $row[$column] = is_numeric($value) ? (int) $value : $value;
            }

            $row['meta']['squadName'] = $this->getSquadName($row['squadId']);
            $row['meta']['teamName'] = $this->getTeamName($row['teamId']);

            if (array_key_exists('type', $row)) {
                $type = $this->getPlayerType($row['type']);
                $row['meta']['type'] = $type;

                if (in_array(strtolower(str_plural($type)), ['spectators', 'commanders'])) {
                    $rows[strtolower(str_plural($type))][] = $row;
                    continue;
                }
            }

            $rows['players'][] = $row;
        }

        $rows['columns'] = $columns;

        return $rows;
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
                /** @noinspection PhpUndefinedMethodInspection */
                Log::debug(socket_strerror(socket_last_error()));
            }

            if ($this->connection) {
                socket_set_block($this->sock);
            }

            $this->sockType = 1;
        } catch (RconException $e) {
            /** @noinspection PhpUndefinedMethodInspection */
            Log::error($e->getMessage());
            try {
                $this->checkFuncs('fsockopen');

                $this->sock = fsockopen('tcp://' . $this->server->ip, $this->server->port, $errno, $errstr, 5);

                if ($debug) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    Log::debug(sprintf('%s - %s', $errno, $errstr));
                }

                $this->connection = $this->sock;
                $this->sockType = 2;
            } catch (RconException $e) {
                /** @noinspection PhpUndefinedMethodInspection */
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
     * @throws \BFACP\Exceptions\Adkats\RconException
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
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($packet, $receiveBuffer) = $this->_receivePacket($receiveBuffer);
        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection PhpUnusedLocalVariableInspection */
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

        while (! $this->_containsCompletePacket($receiveBuffer)) {
            if ($this->sockType == 1) {
                $socketbuffer = @socket_read($this->sock, 4096);
                if ($socketbuffer == false) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    Log::error($errMsg);
                    throw new RconException('Could not read packet data from game server.');
                }
                $receiveBuffer .= $socketbuffer;
            } else {
                $socketbuffer = fread($this->sock, 4096);
                if ($socketbuffer == false) {
                    /** @noinspection PhpUndefinedMethodInspection */
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
