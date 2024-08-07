<?php

namespace pocketmine\metadata;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginException;

abstract class MetadataStore {
	/** @var \SplObjectStorage[] */
	private $metadataMap;

	/**
	 * Adds a metadata value to an object.
	 *
	 * @param Metadatable   $subject
	 * @param string        $metadataKey
	 * @param MetadataValue $newMetadataValue
	 */
	public function setMetadata(Metadatable $subject, string $metadataKey, MetadataValue $newMetadataValue){
		$owningPlugin = $newMetadataValue->getOwningPlugin();
		if($owningPlugin === null){
			throw new PluginException("Plugin cannot be null");
		}

		$key = $this->disambiguate($subject, $metadataKey);
		if(!isset($this->metadataMap[$key])){
			$entry = new \SplObjectStorage();
			$this->metadataMap[$key] = $entry;
		}else{
			$entry = $this->metadataMap[$key];
		}
		$entry[$owningPlugin] = $newMetadataValue;
	}

	/**
	 * Returns all metadata values attached to an object. If multiple
	 * have attached metadata, each will value will be included.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 *
	 * @return MetadataValue[]
	 */
	public function getMetadata(Metadatable $subject, string $metadataKey){
		$key = $this->disambiguate($subject, $metadataKey);
		if(isset($this->metadataMap[$key])){
			return $this->metadataMap[$key];
		}else{
			return [];
		}
	}

	/**
	 * Tests to see if a metadata attribute has been set on an object.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 *
	 * @return bool
	 */
	public function hasMetadata(Metadatable $subject, string $metadataKey) : bool{
		return isset($this->metadataMap[$this->disambiguate($subject, $metadataKey)]);
	}

	/**
	 * Removes a metadata item owned by a plugin from a subject.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 * @param Plugin      $owningPlugin
	 */
	public function removeMetadata(Metadatable $subject, string $metadataKey, Plugin $owningPlugin){
		$key = $this->disambiguate($subject, $metadataKey);
		if(isset($this->metadataMap[$key])){
			unset($this->metadataMap[$key][$owningPlugin]);
			if($this->metadataMap[$key]->count() === 0){
				unset($this->metadataMap[$key]);
			}
		}
	}

	/**
	 * Invalidates all metadata in the metadata store that originates from the
	 * given plugin. Doing this will force each invalidated metadata item to
	 * be recalculated the next time it is accessed.
	 *
	 * @param Plugin $owningPlugin
	 */
	public function invalidateAll(Plugin $owningPlugin){
		/** @var MetadataValue[] $values */
		foreach($this->metadataMap as $values){
			if(isset($values[$owningPlugin])){
				$values[$owningPlugin]->invalidate();
			}
		}
	}

	/**
	 * Creates a unique name for the object receiving metadata by combining
	 * unique data from the subject with a metadataKey.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	public abstract function disambiguate(Metadatable $subject, $metadataKey);
}