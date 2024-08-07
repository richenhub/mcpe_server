<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace raklib\protocol;

use function ceil;
use function chr;
use function ord;
use function strlen;
use function substr;
#ifndef COMPILE
use pocketmine\utils\Binary;
#endif

#include <rules/RakLibPacket.h>

class EncapsulatedPacket{
	const RELIABILITY_SHIFT = 5;
	const RELIABILITY_FLAGS = 0b111 << self::RELIABILITY_SHIFT;
	
	const SPLIT_FLAG = 0b00010000;

	public $reliability;
	public $hasSplit = false;
	public $length = 0;
	public $messageIndex;
	/** @var int|null */
	public $sequenceIndex;
	/** @var int|null */
	public $orderIndex;
	public $orderChannel;
	public $splitCount;
	public $splitID;
	public $splitIndex;
	public $buffer = "";
	public $needACK = false;
	public $identifierACK;

	/**
	 * Decodes an EncapsulatedPacket from bytes generated by toInternalBinary().
	 *
	 * @param string   $bytes
	 * @param int|null &$offset Will be set to the number of bytes read
	 *
	 * @return EncapsulatedPacket
	 */
	public static function fromInternalBinary(string $bytes, ?int &$offset = null) : EncapsulatedPacket{
		$packet = new EncapsulatedPacket();

		$offset = 0;
		$packet->reliability = ord($bytes[$offset++]);

		$length = Binary::readInt(substr($bytes, $offset, 4));
		$offset += 4;
		$packet->identifierACK = Binary::readInt(substr($bytes, $offset, 4)); //TODO: don't read this for non-ack-receipt reliabilities
		$offset += 4;

		if(PacketReliability::isSequencedOrOrdered($packet->reliability)){
			$packet->orderChannel = ord($bytes[$offset++]);
		}

		$packet->buffer = substr($bytes, $offset, $length);
		$offset += $length;
		return $packet;
	}

	/**
	 * Encodes data needed for the EncapsulatedPacket to be transmitted from RakLib to the implementation's thread.
	 * @return string
	 */
	public function toInternalBinary() : string{
		return
			chr($this->reliability) .
			Binary::writeInt(strlen($this->buffer)) .
			Binary::writeInt($this->identifierACK ?? -1) . //TODO: don't write this for non-ack-receipt reliabilities
			(PacketReliability::isSequencedOrOrdered($this->reliability) ? chr($this->orderChannel) : "") .
			$this->buffer;
	}

	/**
	 * @param string $binary
	 * @param int    &$offset
	 *
	 * @return EncapsulatedPacket
	 */
	public static function fromBinary($binary, &$offset = null){
		$packet = new EncapsulatedPacket();

		$flags = ord($binary[0]);
		$packet->reliability = $reliability = ($flags & self::RELIABILITY_FLAGS) >> self::RELIABILITY_SHIFT;
		$packet->hasSplit = $hasSplit = ($flags & self::SPLIT_FLAG) > 0;
		
		$length = (int) ceil(Binary::readShort(substr($binary, 1, 2)) / 8);
		$offset = 3;

		if(PacketReliability::isReliable($reliability)){
			$packet->messageIndex = Binary::readLTriad(substr($binary, $offset, 3));
			$offset += 3;
		}

		if(PacketReliability::isSequenced($reliability)){
			$packet->sequenceIndex = Binary::readLTriad(substr($binary, $offset, 3));
			$offset += 3;
		}

		if(PacketReliability::isSequencedOrOrdered($reliability)){
			$packet->orderIndex = Binary::readLTriad(substr($binary, $offset, 3));
			$offset += 3;
			$packet->orderChannel = ord($binary[$offset++]);
		}

		if($hasSplit){
			$packet->splitCount = Binary::readInt(substr($binary, $offset, 4));
			$offset += 4;
			$packet->splitID = Binary::readShort(substr($binary, $offset, 2));
			$offset += 2;
			$packet->splitIndex = Binary::readInt(substr($binary, $offset, 4));
			$offset += 4;
		}

		$packet->buffer = substr($binary, $offset, $length);
		$offset += $length;

		return $packet;
	}

	/**
	 * @return string
	 */
	public function toBinary(){
		return
			chr(($this->reliability << self::RELIABILITY_SHIFT) | ($this->hasSplit ? self::SPLIT_FLAG : 0)) .
			Binary::writeShort(strlen($this->buffer) << 3) .
			(PacketReliability::isReliable($this->reliability) ? Binary::writeLTriad($this->messageIndex) : "") .
			(PacketReliability::isSequenced($this->reliability) ? Binary::writeLTriad($this->sequenceIndex) : "") .
			(PacketReliability::isSequencedOrOrdered($this->reliability) ? Binary::writeLTriad($this->orderIndex) . chr($this->orderChannel) : "") .
			($this->hasSplit ? Binary::writeInt($this->splitCount) . Binary::writeShort($this->splitID) . Binary::writeInt($this->splitIndex) : "")
			. $this->buffer;
	}

	public function getTotalLength(){
		return
			1 + //reliability
			2 + //length
			(PacketReliability::isReliable($this->reliability) ? 3 : 0) + //message index
			(PacketReliability::isSequenced($this->reliability) ? 3 : 0) + //sequence index
			(PacketReliability::isSequencedOrOrdered($this->reliability) ? 3 + 1 : 0) + //order index (3) + order channel (1)
			($this->hasSplit ? 4 + 2 + 4 : 0) + //split count (4) + split ID (2) + split index (4)
			strlen($this->buffer);
	}

	public function __toString() : string{
		return $this->toBinary();
	}
}
