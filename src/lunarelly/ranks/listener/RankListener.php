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
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

final class RankListener implements Listener
{
    public function __construct(private LunarRanksPlugin $plugin)
    {
    }

    public function getPlugin(): LunarRanksPlugin
    {
        return $this->plugin;
    }

    /**
     * @priority LOWEST
     * @noinspection PhpUnused
     */
    public function handlePlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $plugin = $this->getPlugin();
        $rank = $plugin->getRankFromDatabase($player->getName());

        if ($plugin->doesRankExist($rank)) {
            $plugin->addRank($player, $rank);
        } else {
            $plugin->setRank($player, $plugin->getDefaultRank());
        }

        $plugin->setAttachment($player, $player->addAttachment($plugin));
        $plugin->updatePermissions($player);
    }

    /**
     * @priority LOWEST
     * @noinspection PhpUnused
     */
    public function handlePlayerRankChange(PlayerRankChangeEvent $event): void
    {
        $this->getPlugin()->updatePermissions($event->getPlayer());
    }

    /**
     * @priority LOWEST
     * @noinspection PhpUnused
     */
    public function handlePlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $plugin = $this->getPlugin();

        $plugin->removeRank($player);
        $plugin->removeAttachment($player);
    }
}