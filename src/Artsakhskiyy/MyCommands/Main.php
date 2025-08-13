<?php
declare(strict_types=1);

namespace Artsakhskiyy\MyCommands;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\Config;
use pocketmine\world\sound\XpCollectSound; // пример звука

class Main extends PluginBase {

    private Config $config;
    private array $messages;
    public const DEFAULT_PERMISSION = "mycommands.use";

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();

        $this->messages = $this->config->get("messages", [
            "no_permission" => "§cYou do not have permission to use this command...",
            "only_ingame" => "§cCommand is only available in the game..."
        ]);

        $pm = PermissionManager::getInstance();
        if ($pm->getPermission(self::DEFAULT_PERMISSION) === null) {
            $pm->addPermission(new Permission(self::DEFAULT_PERMISSION, "Using all custom commands...", []));
        }

        $soundConfig = $this->config->get("sound", [
            "enabled" => true,
            "name" => "random.orb",
            "volume" => 1.0,
            "pitch" => 1.0
        ]);

        foreach ($this->config->get("commands", []) as $cmdName => $cmdData) {
            $description = $cmdData["description"] ?? "Custom command...";
            $message = $cmdData["message"] ?? "";
            $messages = $this->messages;

            $cmd = new class($cmdName, $description, $message, $messages, $soundConfig) extends Command {
                private string $message;
                private array $messages;
                private array $soundConfig;

                public function __construct(string $name, string $description, string $message, array $messages, array $soundConfig) {
                    parent::__construct($name, $description);
                    $this->setPermission(Main::DEFAULT_PERMISSION);
                    $this->message = $message;
                    $this->messages = $messages;
                    $this->soundConfig = $soundConfig;
                }

                public function execute(CommandSender $sender, string $commandLabel, array $args): void {
                    if ($sender instanceof Player) {
                        if ($sender->hasPermission(Main::DEFAULT_PERMISSION) || $sender->isOp()) {
                            $sender->sendMessage($this->message);

                            if (!empty($this->soundConfig["enabled"])) {
                                // Тут я оставил только пример с XpCollectSound, т.к. динамически все звуки через строку не вызвать без маппинга
                                $sender->getWorld()->addSound($sender->getPosition(), new XpCollectSound(), [$sender]);
                            }

                        } else {
                            $sender->sendMessage($this->messages["no_permission"] ?? "§cYou do not have permission to use this command...");
                        }
                    } else {
                        $sender->sendMessage($this->messages["only_ingame"] ?? "§cCommand is only available in the game...");
                    }
                }
            };

            $this->getServer()->getCommandMap()->register($cmdName, $cmd);
        }
    }
}
