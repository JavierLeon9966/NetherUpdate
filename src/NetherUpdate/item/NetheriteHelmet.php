<?php
declare (strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Armor;
class NetheriteHelmet extends Armor{
    use AEquipTrait, NetheriteTrait;
	public function __construct(int $meta = 0){
		parent::__construct(ItemIds::NETHERITE_HELMET, $meta, "Netherite ".$this->getType());
	}
	public function getDefensePoints() : int{
		return 3;
	}
	public function getMaxDurability() : int{
		return 408;
	}
}