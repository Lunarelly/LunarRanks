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

namespace lunarelly\ranks\object;

class Rank
{
    public function __construct(
        private string $name,
        private int $priority,
        private string $color,
        private string $displayName,
        private string $chatFormat,
        private string $nameTag,
        private array $permissions
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getChatFormat(): string
    {
        return $this->chatFormat;
    }

    public function getNameTag(): string
    {
        return $this->nameTag;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }
}