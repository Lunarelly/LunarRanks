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

namespace lunarelly\ranks\event;

use lunarelly\ranks\object\Rank;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerRankChangeEvent extends PlayerEvent
{
	public function __construct(Player $player, private readonly Rank $oldRank)
	{
		$this->player = $player;
	}

	public function getOldRank(): Rank
	{
		return $this->oldRank;
	}
}