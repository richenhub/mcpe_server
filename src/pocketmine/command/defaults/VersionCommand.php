<?php

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class VersionCommand extends VanillaCommand {

	/**
	 * VersionCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.version.description",
			"%pocketmine.command.version.usage",
			["ver", "about"]
		);
		$this->setPermission("pocketmine.command.version");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $currentAlias
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$this->testPermission($sender)){
			return \true;
		}

		if(\count($args) === 0){
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended.title"));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended1", [
				$sender->getServer()->getName(),
				$sender->getServer()->getCodename()
			]));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended2", [
				phpversion()
			]));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended3", [
				$sender->getServer()->getApiVersion()

			]));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended4", [
				$sender->getServer()->getVersion()
			]));
		}else{
			$pluginName = \implode(" ", $args);
			$exactPlugin = $sender->getServer()->getPluginManager()->getPlugin($pluginName);

			if($exactPlugin instanceof Plugin){
				$this->describeToSender($exactPlugin, $sender);

				return \true;
			}

			$found = \false;
			$pluginName = \strtolower($pluginName);
			foreach($sender->getServer()->getPluginManager()->getPlugins() as $plugin){
				if(\stripos($plugin->getName(), $pluginName) !== \false){
					$this->describeToSender($plugin, $sender);
					$found = \true;
				}
			}

			if(!$found){
				$sender->sendMessage(new TranslationContainer("pocketmine.command.version.noSuchPlugin"));
			}
		}

		return \true;
	}
}