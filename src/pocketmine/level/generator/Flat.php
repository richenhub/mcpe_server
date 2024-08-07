<?php

declare(strict_types = 1);

namespace pocketmine\level\generator;

use pocketmine\block\CoalOre;
use pocketmine\block\DiamondOre;
use pocketmine\block\Dirt;
use pocketmine\block\GoldOre;
use pocketmine\block\Gravel;
use pocketmine\block\IronOre;
use pocketmine\block\LapisOre;
use pocketmine\block\RedstoneOre;
use pocketmine\item\Item;
use pocketmine\level\loadchunk\ChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\generator\populator\Ore;
use pocketmine\level\generator\populator\Populator;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class Flat extends Generator {
	/** @var ChunkManager */
	private $level;
	/** @var Chunk */
	private $chunk;
	/** @var Random */
	private $random;
	/** @var Populator[] */
	private $populators = [];
	/** @var int[][] */
	private $structure;
	/** @var int */
	private $floorLevel;
	/** @var mixed[] */
	private $options;
	/** @var string */
	private $preset;

	/**
	 * @return array
	 */
	public function getSettings(){
		return $this->options;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "flat";
	}

	/**
	 * Flat constructor.
	 *
	 * @param array $options
	 */
	public function __construct(array $options = []){
		$this->preset = "2;7,2x3,2;1;";
		$this->options = $options;
		$this->chunk = null;

		if(isset($this->options["decoration"])){
			$ores = new Ore();
			$ores->setOreTypes([
				new object\OreType(new CoalOre(), 20, 16, 0, 128),
				new object\OreType(New IronOre(), 20, 8, 0, 64),
				new object\OreType(new RedstoneOre(), 8, 7, 0, 16),
				new object\OreType(new LapisOre(), 1, 6, 0, 32),
				new object\OreType(new GoldOre(), 2, 8, 0, 32),
				new object\OreType(new DiamondOre(), 1, 7, 0, 16),
				new object\OreType(new Dirt(), 20, 32, 0, 128),
				new object\OreType(new Gravel(), 10, 16, 0, 128),
			]);
			$this->populators[] = $ores;
		}

	}

	/**
	 * @param string $layers
	 *
	 * @return array
	 */
	public static function parseLayers(string $layers) : array{
		$result = [];
		preg_match_all('#^(([0-9]*x|)([0-9]{1,3})(|:[0-9]{0,2}))$#m', str_replace(",", "\n", $layers), $matches);
		$y = 0;
		foreach($matches[3] as $i => $b){
			$b = Item::fromString($b . $matches[4][$i]);
			$cnt = $matches[2][$i] === "" ? 1 : intval($matches[2][$i]);
			for($cY = $y, $y += $cnt; $cY < $y; ++$cY){
				$result[$cY] = [$b->getId(), $b->getDamage()];
			}
		}

		return $result;
	}

	protected function parsePreset($preset, $chunkX, $chunkZ){
		$this->preset = $preset;
		$preset = explode(";", $preset);
		$blocks = (string) ($preset[1] ?? "");
		$biome = (int) ($preset[2] ?? 1);
		$options = (string) ($preset[3] ?? "");
		$this->structure = self::parseLayers($blocks);

		$this->floorLevel = $y = count($this->structure);

		$this->chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$this->chunk->setGenerated();

		for($Z = 0; $Z < 16; ++$Z){
			for($X = 0; $X < 16; ++$X){
				$this->chunk->setBiomeId($X, $Z, $biome);
				}
		}

		$count = count($this->structure);
		for($sy = 0; $sy < $count; $sy += 16){
			$subchunk = $this->chunk->getSubChunk($sy >> 4, true);
			for($y = 0; $y < 16 and isset($this->structure[$y | $sy]); ++$y){
				list($id, $meta) = $this->structure[$y | $sy];

				for($Z = 0; $Z < 16; ++$Z){
					for($X = 0; $X < 16; ++$X){
						$subchunk->setBlock($X, $y, $Z, $id, $meta);
					}
				}
			}
		}

        //TODO: more error checking
		preg_match_all('#(([0-9a-z_]{1,})\(?([0-9a-z_ =:]{0,})\)?),?#', $options, $matches);
		foreach($matches[2] as $i => $option){
			$params = true;
			if($matches[3][$i] !== ""){
				$params = [];
				$p = explode(" ", $matches[3][$i]);
				foreach($p as $k){
					$k = explode("=", $k);
					if(isset($k[1])){
						$params[$k[0]] = $k[1];
					}
				}
			}
			$this->options[$option] = $params;
		}
	}

	public function init(ChunkManager $level, Random $random){
		$this->level = $level;
		$this->random = $random;

		/*
		  // Commented out : We want to delay this
		if(isset($this->options["preset"]) and $this->options["preset"] != ""){
			$this->parsePreset($this->options["preset"]);
		}else{
			$this->parsePreset($this->preset);
		}
		*/
	}

	public function generateChunk($chunkX, $chunkZ){
		if($this->chunk === null){
			if(isset($this->options["preset"]) and $this->options["preset"] != ""){
				$this->parsePreset($this->options["preset"], $chunkX, $chunkZ);
			}else{
				$this->parsePreset($this->preset, $chunkX, $chunkZ);
			}
		}
		$chunk = clone $this->chunk;
		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk($chunkX, $chunkZ){
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		foreach($this->populators as $populator){
			$populator->populate($this->level, $chunkX, $chunkZ, $this->random);
		}

	}

	public function getSpawn(){
		return new Vector3(128, $this->floorLevel, 128);
	}
}
