<?php 

namespace Richen\Commands\Player\Teleport;

use Richen\Custom\NBXPlayer;

class tpa extends \Richen\NubixCmds {
    public function __construct($name) {
        parent::__construct($name, 'Запрос на телепортацию');
    }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        if (!count($args)) return $sender->sendMessage($this->getUsageMessage('[игрок]'));
        $player = $this->core()->getServer()->getPlayerExact($args[0]);
        $tpmn = $sender->teleportManager();
        if ($player instanceof NBXPlayer) {
            $sender->sendMessage('§2[!] §7Вы отправили запрос на телепортацию игроку §e' . $player->getName());
            $player->sendMessage('§2[!] §7Вам поступил запрос на телепортацию от игрока §e' . $sender->getName());
            $player->sendMessage('§2[!] §7Чтобы принять запрос используйте: §a/tpc§7, для отмены: §6/tpd');
            $tpmn->setRequests($player);
        } else {
            $sender->sendMessage($this->getOfflineMessage($args[0]));
        }
    }
}