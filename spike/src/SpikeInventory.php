<?php

declare(strict_types=1);

namespace kim\present\libcontainerspike;

use pocketmine\inventory\SimpleInventory;

/**
 * Marker inventory: opened through a packet-only entity instead of a block.
 */
final class SpikeInventory extends SimpleInventory{

	public function __construct(
		int $size,
		public readonly string $entityType,
		public readonly int $windowType,
		public readonly string $title
	){
		parent::__construct($size);
	}
}
