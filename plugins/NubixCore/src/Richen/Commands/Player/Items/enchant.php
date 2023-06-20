<?php 

namespace Richen\Commands\Player\Items;

class enchant extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Чарование'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
    }
}