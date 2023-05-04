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
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;

final class CommandArgs
{
    public const FLAG_NORMAL = 0;

    private array $parameters = [];
    private int $flags;
    private int $permission;

    public function __construct(int $flags = self::FLAG_NORMAL, int $permission = PlayerPermissions::MEMBER)
    {
        $this->flags = $flags;
        $this->permission = $permission;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getPermission(): int
    {
        return $this->permission;
    }

    public function addParameter(int $columnId, string $name, int $type = AvailableCommandsPacket::ARG_TYPE_RAWTEXT, bool $isOptional = false, string $enumName = null, array $enumValues = [], bool $customType = false, string $postfix = null): int
    {
        $param = new CommandParameter();
        $param->paramName = $name;
        $param->paramType = $customType? $type : AvailableCommandsPacket::ARG_FLAG_VALID | $type;
        $param->isOptional = $isOptional;
        $param->postfix = $postfix;

        $this->parameters[$columnId][] = $param;
        $columnKey = count($this->parameters[$columnId]) - 1;

        if ($enumName !== null) {
            $this->setEnum($columnId, $columnKey, $enumName, $enumValues);
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

    public function getOverload(): array
    {
        return $this->parameters;
    }
}