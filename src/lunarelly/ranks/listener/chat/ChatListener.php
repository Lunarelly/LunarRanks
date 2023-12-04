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

namespace lunarelly\ranks\listener\chat;

use lunarelly\ranks\event\PlayerRankChangeEvent;
use lunarelly\ranks\LunarRanks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\utils\BroadcastLoggerForwarder;
use pocketmine\utils\TextFormat;

final class ChatListener implements Listener
{
	private readonly int $localChatDistance;
	private readonly string $globalSymbol;
	private readonly string $localPrefix;
	private readonly string $globalPrefix;

	public function __construct(private readonly LunarRanks $plugin)
	{
		if ($this->plugin->isLocalChatEnabled()) {
			$settings = $this->plugin->getLocalChatSettings();
			$this->localChatDistance = (int)$settings["distance"];
			$this->globalSymbol = (string)$settings["symbol"];
			$this->localPrefix = (string)$settings["local-prefix"];
			$this->globalPrefix = (string)$settings["global-prefix"];
		}
	}

	/** @noinspection PhpUnused */
	public function handlePlayerJoin(PlayerJoinEvent $event): void
	{
		$this->plugin->updateNameTag($event->getPlayer());
	}

	/** @noinspection PhpUnused */
	public function handleRankChange(PlayerRankChangeEvent $event): void
	{
		$this->plugin->updateNameTag($event->getPlayer());
	}

	private function formatChat(Player $player, string $message, ChatType $type = ChatType::Default): string
	{
		return match ($type) {
			ChatType::Default => str_replace(
				["{NAME}", "{DISPLAY_NAME}", "{MESSAGE}"],
				[$player->getName(), $player->getDisplayName(), TextFormat::clean($message)],
				$this->plugin->getRank($player)->getChatFormat()
			),
			ChatType::Local => $this->localPrefix . " " . str_replace(
				["{NAME}", "{DISPLAY_NAME}", "{MESSAGE}"],
				[$player->getName(), $player->getDisplayName(), TextFormat::clean($message)],
				$this->plugin->getRank($player)->getChatFormat()
			),
			ChatType::Global => $this->globalPrefix . " " . str_replace(
				["{NAME}", "{DISPLAY_NAME}", "{MESSAGE}"],
				[$player->getName(), $player->getDisplayName(), TextFormat::clean(substr($message, 1))],
				$this->plugin->getRank($player)->getChatFormat()
			)
		};
	}

	/**
	 * @priority HIGHEST
	 * @noinspection PhpUnused
	 */
	public function handlePlayerChat(PlayerChatEvent $event): void
	{
		if ($event->isCancelled()) {
			return;
		}

		$player = $event->getPlayer();
		$message = trim($event->getMessage());
		$recipients = $event->getRecipients();
		$event->cancel();
		if ($this->plugin->isLocalChatEnabled()) {
			if ($message === $this->globalSymbol) {
				return;
			}

			if (str_starts_with($message, $this->globalSymbol)) {
				foreach ($recipients as $recipient) {
					$recipient->sendMessage($this->formatChat($player, $message, ChatType::Global));
				}
			} else {
				foreach ($recipients as $recipient) {
					$localFormat = $this->formatChat($player, $message, ChatType::Local);
					if ($recipient instanceof Player) {
						if ($recipient->getWorld() === $player->getWorld() && $recipient->getLocation()->distance($player->getPosition()) <= $this->localChatDistance) {
							$recipient->sendMessage($localFormat);
						}
					} elseif ($recipient instanceof BroadcastLoggerForwarder) {
						$recipient->sendMessage($localFormat);
					}
				}
			}
		} else {
			foreach ($recipients as $recipient) {
				$recipient->sendMessage($this->formatChat($player, $message));
			}
		}
	}
}