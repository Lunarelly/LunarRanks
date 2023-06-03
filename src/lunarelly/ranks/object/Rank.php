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
        private readonly string $name,
        private readonly int $priority,
        private readonly string $color,
        private readonly string $displayName,
        private readonly string $chatFormat,
        private readonly string $nameTag,
        private readonly array $permissions
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