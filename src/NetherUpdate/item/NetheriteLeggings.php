<?php
declare (strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Armor;
class NetheriteLeggings extends Armor{
    use AEquipTrait, NetheriteTrait;
	public function __construct(int $meta = 0){
	    $this->slot = 2;
		parent::__construct(ItemIds::NETHERITE_LEGGINGS, $meta, "Netherite ".$this->getType());
	}
	public function getDefensePoints() : int{
		return 6;
	}
	public function getMaxDurability() : int{
		return 556;
	}
}