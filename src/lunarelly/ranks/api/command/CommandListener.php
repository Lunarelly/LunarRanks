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

namespace lunarelly\ranks\api\command;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

final class CommandListener implements Listener
{
	public function __construct(private readonly CommandManager $commandManager)
	{
	}

	/** @noinspection PhpUnused */
	public function handleDataPacketSend(DataPacketSendEvent $event): void
	{
		foreach ($event->getPackets() as $packet) {
			if ($packet instanceof AvailableCommandsPacket) {
				foreach ($event->getTargets() as $target) {
					$player = $target->getPlayer();
					if ($player !== null) {
						foreach ($this->getCommandManager()->getCommands() as $command) {
							if (($arg = $command->getCommandArg()) !== null && ($command = $packet->commandData[strtolower($command->getName())] ?? null) !== null) {
								$command->name = $command->getName();
								$command->description = $command->getDescription();
								$command->flags = CommandArgs::FLAG_NORMAL;
								$command->overloads = $arg->getOverloads();
							}
						}
					}
				}
			}
		}
	}

	private function getCommandManager(): CommandManager
	{
		return $this->commandManager;
	}
}