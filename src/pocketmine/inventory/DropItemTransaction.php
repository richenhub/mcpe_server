<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

namespace pocketmine\inventory;

use pocketmine\item\Item;
use pocketmine\Player;

class DropItemTransaction extends BaseTransaction {

	const TRANSACTION_TYPE = Transaction::TYPE_DROP_ITEM;

	protected $inventory = null;

	protected $slot = null;

	protected $sourceItem = null;

	/**
	 * @param Item $droppedItem
	 */
	public function __construct(Item $droppedItem){
		$this->targetItem = $droppedItem;
	}

	/**
	 * @param Item $item
	 */
	public function setSourceItem(Item $item){
		//Nothing to update
	}

	/**
	 * @return null
	 */
	public function getInventory(){
		return null;
	}

	/**
	 * @return null
	 */
	public function getSlot(){
		return null;
	}

	/**
	 * @param Player $source
	 */
	public function sendSlotUpdate(Player $source){
		//Nothing to update
	}

	/**
	 * @return array
	 */
	public function getChange(){
		return ["in" => $this->getTargetItem(),
			"out" => null];
	}

	/**
	 * @param Player $source
	 *
	 * @return bool
	 */
	public function execute(Player $source) : bool{
		$droppedItem = $this->getTargetItem();
        $ignore = false;
		if($source->getServer()->allowInventoryCheats || $source->isCreative() || $source->isRename()){
        $ignore = true;
         }
         if(!$ignore) {
			if(!$source->getFloatingInventory()->contains($droppedItem)){
				return false;
			}
			$source->getFloatingInventory()->removeItem($droppedItem);
		}
		$source->dropItem($droppedItem);
		return true;
	}
}