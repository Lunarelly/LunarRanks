# Ranks
Ranks plugin for PocketMine-MP.

# Features
- Rank command have auto-completion
- Flexible settings in config.yml
- Local/Global chat
- Rank inheritances
- Rank priorities
- Chat & Nametags included
- Fast & Easy to use

# Setup
- Configure your ranks in config.yml
- Example:
```yaml
ranks:
  player:
    priority: 1
    color: "§7"
    display-name: "Player"
    chat-format: "§f{NAME}: §7{MESSAGE}" # Available tags: {NAME}, {DISPLAY_NAME}, {MESSAGE}
    name-tag: "§7{NAME}" # Available tags: {NAME}, {DISPLAY_NAME}, {PLATFORM}, {LINE}
    alias: "default"
    inheritance: []
    permissions: []
```
- Use /rank command!

# API
- Example:
```php
/** @var Ranks $rankManager */
$rankManager = Server::getInstance()->getPluginManager()->getPlugin("Ranks");

if ($rankManager !== null) {
    $player->sendMessage("Your rank: " . $rankManager->getRank($player)->getDisplayName());
}
```
