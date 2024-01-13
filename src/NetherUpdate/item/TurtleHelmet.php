<?php
declare (strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Armor;
class TurtleHelmet extends Armor{
    use AEquipTrait;
	public function __construct(int $meta = 0){
		parent::__construct(self::TURTLE_HELMET, $meta, "Turtle ".$this->getType());
	}
	public function getDefensePoints() : int{
		return 2;
	}
	public function getMaxDurability() : int{
		return 276;
	}
}