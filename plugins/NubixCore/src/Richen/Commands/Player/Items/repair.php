<?php 

namespace Richen\Commands\Player\Items;

class repair extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Починка предметов'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        $item = $sender->getInventory()->getItemInHand();
        if (!$item->isTool() || !$item->isArmor() || $item->isNull()) return $sender->sendMessage($this->lang()::ERR . ' §cЭтот предмет починить нельзя');
        if (($cd = $this->countdown($sender, 30)) > 0) return $sender->sendMessage($this->lang()::ERR . ' §cНе так часто! Подождите ещё ' . $cd . ' сек.');
        $item->setDamage(0);
        $sender->getInventory()->setItemInHand($item);
        $sender->sendMessage($this->lang()::SUC . ' §fВы починили предмет в вашей руке');
    }
}