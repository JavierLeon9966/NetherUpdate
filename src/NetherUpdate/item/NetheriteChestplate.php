<?php
declare (strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Armor;
class NetheriteChestplate extends Armor{
    use AEquipTrait, NetheriteTrait;
	public function __construct(int $meta = 0){
	    $this->slot = 1;
		parent::__construct(ItemIds::NETHERITE_CHESTPLATE, $meta, "Netherite ".$this->getType());
	}
	public function getDefensePoints() : int{
		return 8;
	}
	public function getMaxDurability() : int{
		return 593;
	}
}