<?php

namespace pocketmine\entity;

abstract class WaterAnimal extends Creature implements Ageable {

	/**
	 * @return bool
	 */
	public function isBaby(){
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_BABY);
	}
}
