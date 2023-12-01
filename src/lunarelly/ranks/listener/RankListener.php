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
use lunarelly\ranks\LunarRanks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

final class RankListener implements Listener
{
	public function __construct(private readonly LunarRanks $plugin)
	{
	}

	/**
	 * @priority LOWEST
	 * @noinspection PhpUnused
	 */
	public function handlePlayerJoin(PlayerJoinEvent $event): void
	{
		$player = $event->getPlayer();
		$rank = $this->plugin->getRankFromDatabase($player->getName());

		if ($this->plugin->doesRankExist($rank)) {
			$this->plugin->addRank($player, $rank);
		} else {
			$this->plugin->setRank($player, $this->plugin->getDefaultRank());
		}

		$this->plugin->setAttachment($player, $player->addAttachment($this->plugin));
		$this->plugin->updatePermissions($player);
	}

	/**
	 * @priority LOWEST
	 * @noinspection PhpUnused
	 */
	public function handlePlayerRankChange(PlayerRankChangeEvent $event): void
	{
		$this->plugin->updatePermissions($event->getPlayer());
	}

	/**
	 * @priority HIGHEST
	 * @noinspection PhpUnused
	 */
	public function handlePlayerQuit(PlayerQuitEvent $event): void
	{
		$player = $event->getPlayer();
		$this->plugin->removeRank($player);
		$this->plugin->removeAttachment($player);
	}
}