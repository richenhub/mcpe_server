<?php
/**
 * Different noise generators for level generation
 */

namespace pocketmine\level\generator\noise;


abstract class Noise {
	protected $perm = [];
	protected $offsetX = 0;
	protected $offsetY = 0;
	protected $offsetZ = 0;
	protected $octaves = 8;
	protected $persistence;
	protected $expansion;

	/**
	 * @param $x
	 *
	 * @return int
	 */
	public static function floor($x){
		return $x >= 0 ? (int) $x : (int) ($x - 1);
	}

	/**
	 * @param $x
	 *
	 * @return mixed
	 */
	public static function fade($x){
		return $x * $x * $x * ($x * ($x * 6 - 15) + 10);
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $z
	 *
	 * @return mixed
	 */
	public static function lerp($x, $y, $z){
		return $y + $x * ($z - $y);
	}

	/**
	 * @param $x
	 * @param $x1
	 * @param $x2
	 * @param $q0
	 * @param $q1
	 *
	 * @return float|int
	 */
	public static function linearLerp($x, $x1, $x2, $q0, $q1){
		return (($x2 - $x) / ($x2 - $x1)) * $q0 + (($x - $x1) / ($x2 - $x1)) * $q1;
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $q00
	 * @param $q01
	 * @param $q10
	 * @param $q11
	 * @param $x1
	 * @param $x2
	 * @param $y1
	 * @param $y2
	 *
	 * @return float|int
	 */
	public static function bilinearLerp($x, $y, $q00, $q01, $q10, $q11, $x1, $x2, $y1, $y2){
		$dx1 = (($x2 - $x) / ($x2 - $x1));
		$dx2 = (($x - $x1) / ($x2 - $x1));

		return (($y2 - $y) / ($y2 - $y1)) * (
				$dx1 * $q00 + $dx2 * $q10
			) + (($y - $y1) / ($y2 - $y1)) * (
				$dx1 * $q01 + $dx2 * $q11
			);
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $z
	 * @param $q000
	 * @param $q001
	 * @param $q010
	 * @param $q011
	 * @param $q100
	 * @param $q101
	 * @param $q110
	 * @param $q111
	 * @param $x1
	 * @param $x2
	 * @param $y1
	 * @param $y2
	 * @param $z1
	 * @param $z2
	 *
	 * @return float|int
	 */
	public static function trilinearLerp($x, $y, $z, $q000, $q001, $q010, $q011, $q100, $q101, $q110, $q111, $x1, $x2, $y1, $y2, $z1, $z2){
		$dx1 = (($x2 - $x) / ($x2 - $x1));
		$dx2 = (($x - $x1) / ($x2 - $x1));
		$dy1 = (($y2 - $y) / ($y2 - $y1));
		$dy2 = (($y - $y1) / ($y2 - $y1));

		return (($z2 - $z) / ($z2 - $z1)) * (
				$dy1 * (
					$dx1 * $q000 + $dx2 * $q100
				) + $dy2 * (
					$dx1 * $q001 + $dx2 * $q101
				)
			) + (($z - $z1) / ($z2 - $z1)) * (
				$dy1 * (
					$dx1 * $q010 + $dx2 * $q110
				) + $dy2 * (
					$dx1 * $q011 + $dx2 * $q111
				)
			);
	}

	/**
	 * @param $hash
	 * @param $x
	 * @param $y
	 * @param $z
	 *
	 * @return mixed
	 */
	public static function grad($hash, $x, $y, $z){
		$hash &= 15;
		$u = $hash < 8 ? $x : $y;
		$v = $hash < 4 ? $y : (($hash === 12 or $hash === 14) ? $x : $z);

		return (($hash & 1) === 0 ? $u : -$u) + (($hash & 2) === 0 ? $v : -$v);
	}

	/**
	 * @param $x
	 * @param $z
	 *
	 * @return mixed
	 */
	abstract public function getNoise2D($x, $z);

	/**
	 * @param $x
	 * @param $y
	 * @param $z
	 *
	 * @return mixed
	 */
	abstract public function getNoise3D($x, $y, $z);

	/**
	 * @param      $x
	 * @param      $z
	 * @param bool $normalized
	 *
	 * @return int
	 */
	public function noise2D($x, $z, $normalized = false){
		$result = 0;
		$amp = 1;
		$freq = 1;
		$max = 0;

		$x *= $this->expansion;
		$z *= $this->expansion;

		for($i = 0; $i < $this->octaves; ++$i){
			$result += $this->getNoise2D($x * $freq, $z * $freq) * $amp;
			$max += $amp;
			$freq *= 2;
			$amp *= $this->persistence;
		}

		if($normalized === true){
			$result /= $max;
		}

		return $result;
	}

	/**
	 * @param      $x
	 * @param      $y
	 * @param      $z
	 * @param bool $normalized
	 *
	 * @return int
	 */
	public function noise3D($x, $y, $z, $normalized = false){
		$result = 0;
		$amp = 1;
		$freq = 1;
		$max = 0;

		$x *= $this->expansion;
		$y *= $this->expansion;
		$z *= $this->expansion;

		for($i = 0; $i < $this->octaves; ++$i){
			$result += $this->getNoise3D($x * $freq, $y * $freq, $z * $freq) * $amp;
			$max += $amp;
			$freq *= 2;
			$amp *= $this->persistence;
		}

		if($normalized === true){
			$result /= $max;
		}

		return $result;
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $z
	 */
	public function setOffset($x, $y, $z){
		$this->offsetX = $x;
		$this->offsetY = $y;
		$this->offsetZ = $z;
	}
}