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

namespace lunarelly\ranks\command;

use lunarelly\ranks\api\command\CommandArgs;
use lunarelly\ranks\api\command\ExtendedCommand;
use lunarelly\ranks\LunarRanks;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;

final class RankCommand extends ExtendedCommand implements PluginOwned
{
	public function __construct(private readonly LunarRanks $plugin)
	{
		$command = $this->plugin->getMessages()["rank-command"];
		parent::__construct($command["name"], $command["description"], $command["global-usage"], $command["aliases"]);

		$this->setPermission("lunarranks.command.rank");

		$this->commandArg = new CommandArgs();
		$this->commandArg->addParameter(0, "player", AvailableCommandsPacket::ARG_TYPE_TARGET);
		$key = $this->commandArg->addParameter(0, "rank", AvailableCommandsPacket::ARG_FLAG_ENUM | AvailableCommandsPacket::ARG_TYPE_STRING);
		$this->commandArg->setEnum(0, $key, "rank", $this->plugin->getRankList());
	}

	public function getOwningPlugin(): LunarRanks
	{
		return $this->plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if (!($this->testPermission($sender))) {
			return false;
		}

		$plugin = $this->getOwningPlugin();
		$messages = $plugin->getMessages();
		if ($sender instanceof Player) {
			if (count($args) < 2) {
				$sender->sendMessage($messages["rank-command"]["player-usage"]);
				return false;
			}

			$rank = $plugin->getRankFromAlias(strtolower($args[1]));
			if (!($plugin->doesRankExist($rank))) {
				$sender->sendMessage(str_replace("{RANK}", $rank, $messages["player"]["rank-does-not-exist"]));
				return false;
			}

			$player = $plugin->getServer()->getPlayerExact($args[0]);
			if ($player !== null) {
				if ($sender === $player) {
					$sender->sendMessage($messages["player"]["cannot-change-self-rank"]);
					return false;
				}

				$senderRankPriority = $plugin->getRank($sender)->getPriority();
				$playerRank = $plugin->getRank($player);

				if ($senderRankPriority <= $playerRank->getPriority()) {
					$sender->sendMessage(str_replace("{PLAYER}", $player->getName(), $messages["player"]["cannot-change-player-rank"]));
					return false;
				}

				$rankDisplay = $plugin->getRankDisplayName($rank);
				if ($senderRankPriority <= $plugin->getRankPriority($rank)) {
					$sender->sendMessage(str_replace("{RANK}", $rankDisplay, $messages["player"]["cannot-set-this-rank"]));
					return false;
				}

				if ($playerRank->getName() === $rank) {
					$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$player->getName(), $rankDisplay], $messages["player"]["player-already-have-that-rank"]));
					return false;
				}

				$plugin->setRank($player, $rank);
				$player->sendMessage(str_replace("{RANK}", $rankDisplay, $messages["player"]["rank-changed"]));
				$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$player->getName(), $rankDisplay], $messages["player"]["rank-changed-another"]));
				$plugin->notifyRankChange($sender->getName(), $player->getName(), $rankDisplay);
			} else {
				$nickname = strtolower($args[0]);
				$senderRankPriority = $plugin->getRank($sender)->getPriority();
				$playerRank = $plugin->getRankFromDatabase($nickname);

				if ($senderRankPriority <= $plugin->getRankPriority($playerRank)) {
					$sender->sendMessage(str_replace("{PLAYER}", $nickname, $messages["player"]["cannot-change-player-rank"]));
					return false;
				}

				$rankDisplay = $plugin->getRankDisplayName($rank);
				if ($senderRankPriority <= $plugin->getRankPriority($rank)) {
					$sender->sendMessage(str_replace("{RANK}", $rankDisplay, $messages["player"]["cannot-set-this-rank"]));
					return false;
				}

				if ($playerRank === $rank) {
					$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$nickname, $rankDisplay], $messages["player"]["player-already-have-that-rank"]));
					return false;
				}

				$plugin->setRankOffline($nickname, $rank);
				$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$nickname, $rankDisplay], $messages["player"]["rank-changed-another"]));
				$plugin->notifyRankChange($sender->getName(), $nickname, $rankDisplay);
			}
		} else {
			if (count($args) < 2) {
				$sender->sendMessage($this->getUsage());
				return false;
			}

			$rank = $plugin->getRankFromAlias(strtolower($args[1]));
			if (!($plugin->doesRankExist($rank))) {
				$sender->sendMessage(str_replace("{RANK}", $rank, $messages["console"]["rank-does-not-exist"]));
				return false;
			}

			$player = $plugin->getServer()->getPlayerExact($args[0]);
			if ($player !== null) {
				$rankDisplay = $plugin->getRankDisplayName($rank);
				$playerRank = $plugin->getRank($player);

				if (isset($args[2])) {
					if ($args[2] === $messages["rank-command"]["store-argument"]) {
						if ($plugin->getRankPriority($rank) <= $playerRank->getPriority()) {
							$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$player->getName(), $rankDisplay], $messages["console"]["cannot-change-player-rank-to-this"]));
							return false;
						}
					}
				}

				if ($playerRank->getName() === $rank) {
					$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$player->getName(), $rankDisplay], $messages["console"]["player-already-have-that-rank"]));
					return false;
				}

				$plugin->setRank($player, $rank);
				$player->sendMessage(str_replace("{RANK}", $rankDisplay, $messages["player"]["rank-changed"]));
				$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$player->getName(), $rankDisplay], $messages["console"]["rank-changed-another"]));
				$plugin->notifyRankChange($sender->getName(), $player->getName(), $rankDisplay);
			} else {
				$nickname = strtolower($args[0]);
				$rankDisplay = $plugin->getRankDisplayName($rank);
				$playerRank = $plugin->getRankFromDatabase($nickname);

				if (isset($args[2])) {
					if ($args[2] === $messages["rank-command"]["store-argument"]) {
						if ($plugin->getRankPriority($rank) <= $plugin->getRankPriority($playerRank)) {
							$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$nickname, $rankDisplay], $messages["console"]["cannot-change-player-rank-to-this"]));
							return false;
						}
					}
				}

				if ($playerRank === $rank) {
					$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$nickname, $rankDisplay], $messages["console"]["player-already-have-that-rank"]));
					return false;
				}

				$plugin->setRankOffline($nickname, $rank);
				$sender->sendMessage(str_replace(["{PLAYER}", "{RANK}"], [$nickname, $rankDisplay], $messages["console"]["rank-changed-another"]));
				$plugin->notifyRankChange($sender->getName(), $nickname, $rankDisplay);
			}
		}
		return true;
	}
}