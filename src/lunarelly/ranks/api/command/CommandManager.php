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

use pocketmine\plugin\PluginBase;

final class CommandManager
{
	/** @var ExtendedCommand[] */
	private array $commands = [];

	public function __construct(private readonly PluginBase $plugin)
	{
		$plugin->getServer()->getPluginManager()->registerEvents(new CommandListener($this), $plugin);
	}

	public function getPlugin(): PluginBase
	{
		return $this->plugin;
	}

	public function getCommands(): array
	{
		return $this->commands;
	}

	/** @var ExtendedCommand[] $commands */
	public function setCommands(array $commands): void
	{
		$this->commands = $commands;
	}

	public function addCommand(ExtendedCommand $command): void
	{
		$this->commands[] = $command;
	}
}