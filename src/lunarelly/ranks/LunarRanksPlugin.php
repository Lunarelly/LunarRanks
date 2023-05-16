<?php

/**
 *  _                               _ _
 * | |   _   _ _ __   __ _ _ __ ___| | |_   _
 * | |  | | | |  _ \ / _  |  __/ _ \ | | | | |
 * | |__| |_| | | | | (_| | | |  __/ | | |_| |
 * |_____\____|_| |_|\____|_|  \___|_|_|\___ |
 *                                      |___/
 *
 * @author Lunarelly
 * @link https://github.com/Lunarelly
 *
 */

declare(strict_types=1);

namespace lunarelly\ranks;

use lunarelly\ranks\api\command\CommandManager;
use lunarelly\ranks\command\RankCommand;
use lunarelly\ranks\event\PlayerRankChangeEvent;
use lunarelly\ranks\exception\RanksException;
use lunarelly\ranks\listener\ChatListener;
use lunarelly\ranks\listener\RankListener;
use lunarelly\ranks\object\Rank;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

use LevelDB;

final class LunarRanksPlugin extends PluginBase
{
    private static self $instance;

    private LevelDB $database;

    private array $settings;
    private array $ranks;
    private array $rankList;
    private array $rankInheritances;
    private array $permissions;
    private array $aliasesToRanks;
    private array $messages;

    private string $defaultRank;

    private bool $localChatEnabled;
    private array $localChatSettings;

    /** @var array<string, PermissionAttachment> */
    private array $attachments = [];
    /** @var array<string, Rank> */
    private array $playerRanks = [];

    private function initializeDatabase(): void
    {
        if (!(is_dir($this->getDataFolder() . "data"))) {
            @mkdir($this->getDataFolder() . "data");
        }
        $this->database = new LevelDB($this->getDataFolder() . "data/ranks");
    }

    private function readInheritances(): void
    {
        foreach ($this->ranks as $rankName => $rankData) {
            if ($rankData["inheritance"] !== []) {
                foreach ($rankData["inheritance"] as $inheritance) {
                    $this->rankInheritances[$rankName][] = $inheritance;
                }
            } else {
                $this->rankInheritances[$rankName] = [];
            }
        }
    }

    private function readPermissions(): void
    {
        foreach ($this->ranks as $rankName => $rankData) {
            $permissions = $rankData["permissions"];
            foreach ($this->rankInheritances[$rankName] as $childRank) {
                $permissions = array_merge($permissions, $this->ranks[$childRank]["permissions"]);
            }
            $this->permissions[$rankName] = $permissions;
        }
    }

    private function readAliases(): void
    {
        foreach ($this->ranks as $rankName => $rankData) {
            if ($rankData["alias"] === "") {
                continue;
            }
            $this->aliasesToRanks[$rankData["alias"]] = $rankName;
        }
    }

    private function initializeConfigData(): void
    {
        $configData = $this->getConfig()->getAll();
        $this->settings = $configData["settings"];
        $this->ranks = $configData["ranks"];
        $this->rankList = array_keys($configData["ranks"]);

        $this->readInheritances();
        $this->readPermissions();
        $this->readAliases();

        $this->messages = $configData["messages"];

        $defaultRank = strtolower($configData["settings"]["default-rank"]);
        if (!(in_array($defaultRank, $this->rankList))) {
            throw new RanksException(sprintf("Default rank '%s' does not exist", $defaultRank));
        }

        $this->defaultRank = $defaultRank;
        $this->localChatEnabled = (bool)$configData["settings"]["local-chat"]["enabled"];
        $this->localChatSettings = $configData["settings"]["local-chat"];
    }

    private function registerCommands(): void
    {
        $commandManager = new CommandManager($this);
        $rankCommand = new RankCommand($this);

        $commandManager->addCommand($rankCommand);
        $this->getServer()->getCommandMap()->register("lunarranks", $rankCommand);
    }

    private function registerListeners(): void
    {
        $pluginManager = $this->getServer()->getPluginManager();
        $pluginManager->registerEvents(new RankListener($this), $this);
        $pluginManager->registerEvents(new ChatListener($this), $this);
    }

    protected function onEnable(): void
    {
        self::$instance = $this;

        $this->saveDefaultConfig();

        $this->initializeDatabase();
        $this->initializeConfigData();

        $this->registerCommands();
        $this->registerListeners();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    /** @internal */
    public function getDatabase(): LevelDB
    {
        return $this->database;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getRanks(): array
    {
        return $this->ranks;
    }

    public function getRankList(): array
    {
        return $this->rankList;
    }

    public function getRankInheritances(): array
    {
        return $this->rankInheritances;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getAliasesToRanks(): array
    {
        return $this->aliasesToRanks;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getDefaultRank(): string
    {
        return $this->defaultRank;
    }

    public function doesRankExist(string $rank): bool
    {
        return in_array(strtolower($rank), $this->getRankList(), true);
    }

    public function isDefaultRank(string $rank): bool
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        return strtolower($rank) === $this->getDefaultRank();
    }

    public function isLocalChatEnabled(): bool
    {
        return $this->localChatEnabled;
    }

    public function getLocalChatSettings(): array
    {
        return $this->localChatSettings;
    }

    public function getRankPriority(string $rank): int
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        return (int)$this->getRanks()[strtolower($rank)]["priority"];
    }

    public function getRankColor(string $rank): string
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        return $this->getRanks()[strtolower($rank)]["color"];
    }

    public function getRankDisplayName(string $rank): string
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        return $this->getRanks()[strtolower($rank)]["display-name"];
    }

    public function getRankChatFormat(string $rank): string
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        return $this->getRanks()[strtolower($rank)]["chat-format"];
    }

    public function getRankNameTag(string $rank): string
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        return $this->getRanks()[strtolower($rank)]["name-tag"];
    }

    public function getRankPermissions(string $rank): array
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        return $this->getPermissions()[strtolower($rank)];
    }

    public function getRankFromAlias(string $alias): string
    {
        return $this->getAliasesToRanks()[strtolower($alias)] ?? $alias;
    }

    public function getRank(Player $player): Rank
    {
        $nickname = strtolower($player->getName());
        $rank = $this->getRankFromDatabase($nickname);
        $rankData = $this->getRanks()[$rank];

        return $this->playerRanks[$nickname] ?? new Rank(
            $rank,
            $rankData["priority"],
            $rankData["color"],
            $rankData["display-name"],
            $rankData["chat-format"],
            $rankData["name-tag"],
            $this->getRankPermissions($rank)
        );
    }

    public function getRankFromDatabase(string $nickname): string
    {
        $rank = $this->getDatabase()->get(strtolower($nickname));
        return $rank !== false ? $rank : $this->getDefaultRank();
    }

    public function setRank(Player $player, string $rank): void
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        $rank = strtolower($rank);
        $nickname = strtolower($player->getName());
        $oldRank = $this->getRank($player);

        $database = $this->getDatabase();
        if ($database->get($nickname) !== false) {
            $database->delete($nickname);
        }

        if (!($this->isDefaultRank($rank))) {
            $database->put($nickname, $rank);
        }

        $this->addRank($player, $rank);

        $event = new PlayerRankChangeEvent($player, $oldRank);
        $event->call();
    }

    public function setRankOffline(string $nickname, string $rank): void
    {
        if (!($this->doesRankExist($rank))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rank));
        }

        $rank = strtolower($rank);
        $nickname = strtolower($nickname);

        $database = $this->getDatabase();
        if ($database->get($nickname) !== false) {
            $database->delete($nickname);
        }

        if (!($this->isDefaultRank($rank))) {
            $database->put($nickname, $rank);
        }
    }

    /** @internal */
    public function addRank(Player $player, string $rankName): void
    {
        $rankName = strtolower($rankName);
        if (!($this->doesRankExist($rankName))) {
            throw new RanksException(sprintf("Rank '%s' does not exist", $rankName));
        }

        $this->removeRank($player);

        $rankData = $this->getRanks()[$rankName];
        $this->playerRanks[strtolower($player->getName())] = new Rank(
            $rankName,
            $rankData["priority"],
            $rankData["color"],
            $rankData["display-name"],
            $rankData["chat-format"],
            $rankData["name-tag"],
            $this->getRankPermissions($rankName)
        );
    }

    /** @internal */
    public function removeRank(Player $player): void
    {
        $nickname = strtolower($player->getName());
        if (isset($this->playerRanks[$nickname])) {
            unset($this->playerRanks[$nickname]);
        }
    }

    /** @internal */
    public function getAttachment(Player $player): PermissionAttachment
    {
        $nickname = strtolower($player->getName());
        if (!(isset($this->attachments[$nickname]))) {
            throw new RanksException(sprintf("Player '%s' has no PermissionAttachment?", $nickname));
        }

        return $this->attachments[$nickname];
    }

    /** @internal */
    public function setAttachment(Player $player, PermissionAttachment $attachment): void
    {
        $this->attachments[strtolower($player->getName())] = $attachment;
    }

    /** @internal */
    public function removeAttachment(Player $player): void
    {
        $nickname = strtolower($player->getName());
        if (isset($this->attachments[$nickname])) {
            unset($this->attachments[$nickname]);
        }
    }

    public function updatePermissions(Player $player): void
    {
        $attachment = $this->getAttachment($player);

        $attachment->clearPermissions();
        foreach ($this->getRank($player)->getPermissions() as $permission) {
            if ($permission === "*") {
                foreach (PermissionManager::getInstance()->getPermissions() as $tempPermission) {
                    $attachment->setPermission($tempPermission->getName(), true);
                }
                break;
            }
            $attachment->setPermission($permission, true);
        }
    }

    /** @internal */
    public function notifyRankChange(string $issuer, string $target, string $rank): void
    {
        $messages = $this->getMessages();
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            if ($player->hasPermission("lunarranks.notifications")) {
                $player->sendMessage(str_replace(["{ISSUER}", "{TARGET}", "{RANK}"], [$issuer, $target, $rank], $messages["player"]["rank-change-notification"]));
            }
        }
        $this->getLogger()->info(str_replace(["{ISSUER}", "{TARGET}", "{RANK}"], [$issuer, $target, $rank], $messages["console"]["rank-change-notification"]));
    }

    public function getPlatform(Player $player): string
    {
        $extraData = $player->getPlayerInfo()->getExtraData();

        if ($extraData["DeviceOS"] === DeviceOS::ANDROID && $extraData["DeviceModel"] === "") {
            return "Linux";
        }

        return match ($extraData["DeviceOS"]) {
            DeviceOS::ANDROID => "Android",
            DeviceOS::IOS => "iOS",
            DeviceOS::OSX => "macOS",
            DeviceOS::AMAZON => "Fire OS",
            DeviceOS::GEAR_VR => "Gear VR",
            DeviceOS::HOLOLENS => "Hololens",
            DeviceOS::WINDOWS_10, DeviceOS::WIN32 => "Windows",
            DeviceOS::DEDICATED => "Dedicated",
            DeviceOS::TVOS => "tvOS",
            DeviceOS::PLAYSTATION => "PlayStation",
            DeviceOS::NINTENDO => "Nintendo Switch",
            DeviceOS::XBOX => "Xbox",
            DeviceOS::WINDOWS_PHONE => "Windows Phone",
            default => "Unknown"
        };
    }

    public function updateNameTag(Player $player): void
    {
        $player->setNameTag(str_replace(
            ["{NAME}", "{DISPLAY_NAME}", "{PLATFORM}", "{LINE}"],
            [$player->getName(), $player->getDisplayName(), $this->getPlatform($player), "\n"],
            $this->getRank($player)->getNameTag()
        ));
    }
}