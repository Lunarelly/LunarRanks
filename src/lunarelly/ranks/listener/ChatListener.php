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

namespace lunarelly\ranks\listener;

use lunarelly\ranks\event\PlayerRankChangeEvent;
use lunarelly\ranks\LunarRanksPlugin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\utils\BroadcastLoggerForwarder;
use pocketmine\utils\TextFormat;

final class ChatListener implements Listener
{
    private const TYPE_DEFAULT = 0;
    private const TYPE_LOCAL = 1;
    private const TYPE_GLOBAL = 2;

    private int $localChatDistance = 0;
    private string $symbol = "";
    private string $localPrefix = "";
    private string $globalPrefix = "";

    public function __construct(private readonly LunarRanksPlugin $plugin)
    {
        if ($this->plugin->isLocalChatEnabled()) {
            $settings = $this->plugin->getLocalChatSettings();
            $this->localChatDistance = (int)$settings["distance"];
            $this->symbol = $settings["symbol"];
            $this->localPrefix = $settings["local-prefix"];
            $this->globalPrefix = $settings["global-prefix"];
        }
    }

    private function getPlugin(): LunarRanksPlugin
    {
        return $this->plugin;
    }

    private function getLocalChatDistance(): int
    {
        return $this->localChatDistance;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    private function getLocalPrefix(): string
    {
        return $this->localPrefix;
    }

    private function getGlobalPrefix(): string
    {
        return $this->globalPrefix;
    }

    private function formatChat(Player $player, string $message, int $type = self::TYPE_DEFAULT): string
    {
        $plugin = $this->getPlugin();

        if ($type === self::TYPE_DEFAULT) {
            return str_replace(
                ["{NAME}", "{DISPLAY_NAME}", "{MESSAGE}"],
                [$player->getName(), $player->getDisplayName(), TextFormat::clean($message)],
                $plugin->getRank($player)->getChatFormat()
            );
        } elseif ($type === self::TYPE_LOCAL) {
            return $this->getLocalPrefix() . " " . str_replace(
                ["{NAME}", "{DISPLAY_NAME}", "{MESSAGE}"],
                [$player->getName(), $player->getDisplayName(), TextFormat::clean($message)],
                $plugin->getRank($player)->getChatFormat()
            );
        } elseif ($type === self::TYPE_GLOBAL) {
            return $this->getGlobalPrefix() . " " . str_replace(
                ["{NAME}", "{DISPLAY_NAME}", "{MESSAGE}"],
                [$player->getName(), $player->getDisplayName(), TextFormat::clean(str_ireplace("!", "", $message))],
                $plugin->getRank($player)->getChatFormat()
            );
        } else {
            return "???";
        }
    }

    /** @noinspection PhpUnused */
    public function handlePlayerJoin(PlayerJoinEvent $event): void
    {
        $this->getPlugin()->updateNameTag($event->getPlayer());
    }

    /** @noinspection PhpUnused */
    public function handleRankChange(PlayerRankChangeEvent $event): void
    {
        $this->getPlugin()->updateNameTag($event->getPlayer());
    }

    /** @noinspection PhpUnused */
    public function handlePlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $plugin = $this->getPlugin();
        $message = trim($event->getMessage());
        $recipients = $event->getRecipients();

        $event->cancel();
        if ($plugin->isLocalChatEnabled()) {
            if ($message === $this->getSymbol()) {
                return;
            }

            if ($message[0] !== $this->getSymbol()) {
                foreach ($recipients as $recipient) {
                    $localFormat = $this->formatChat($player, $message, self::TYPE_LOCAL);
                    if ($recipient instanceof Player) {
                        if ($recipient->getLocation()->distance($player->getPosition()) <= $this->getLocalChatDistance()) {
                            $recipient->sendMessage($localFormat);
                        }
                    } elseif ($recipient instanceof BroadcastLoggerForwarder) {
                        $recipient->sendMessage($localFormat);
                    }
                }
            } else {
                foreach ($recipients as $recipient) {
                    $recipient->sendMessage($this->formatChat($player, $message, self::TYPE_GLOBAL));
                }
            }
        } else {
            foreach ($recipients as $recipient) {
                $recipient->sendMessage($this->formatChat($player, $message));
            }
        }
    }
}