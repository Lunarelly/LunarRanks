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

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

class CommandArgs
{
	final public const FLAG_NORMAL = 0;

	/** @var CommandParameter[][] */
	private array $parameters = [];
	/** @var CommandOverload[] */
	private array $overloads = [];

	public function getOverloads(): array
	{
		return $this->overloads;
	}

	public function addParameter(int $columnId, string $name, int $type = AvailableCommandsPacket::ARG_TYPE_RAWTEXT, bool $isOptional = false, string $enumName = null, array $enumValues = [], bool $customType = false, string $postfix = null): int
	{
		$parameter = new CommandParameter();
		$parameter->paramName = $name;
		$parameter->paramType = $customType ? $type : AvailableCommandsPacket::ARG_FLAG_VALID | $type;
		$parameter->isOptional = $isOptional;
		$parameter->postfix = $postfix;

		$this->parameters[$columnId][] = $parameter;
		$columnKey = count($this->parameters[$columnId]) - 1;

		if ($enumName !== null) {
			$this->setEnum($columnId, $columnKey, $enumName, $enumValues);
		}

		$this->overloads = [];
		foreach ($this->parameters as $parameters) {
			$this->overloads[] = new CommandOverload(false, $parameters);
		}

		return $columnKey;
	}

	public function setEnum(int $columnId, int $columnKey, ?string $name, array $values = []): bool
	{
		$parameter = $this->parameters[$columnId][$columnKey] ?? null;
		if ($parameter === null) {
			return false;
		}

		if ($name !== null) {
			$enum = new CommandEnum($name, $values);
		}

		$parameter->enum = $name === null ? null : $enum;
		return true;
	}
}