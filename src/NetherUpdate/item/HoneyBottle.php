<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\{Item, Food};
use pocketmine\entity\{Living, Effect};
class HoneyBottle extends Food{
    public function __construct(int $meta = 0){
        parent::__construct(ItemIds::HONEY_BOTTLE, $meta, "Honey Bottle");
    }
    public function getResidue(){
        return ItemFactory::get(Item::GLASS_BOTTLE);
    }
	public function onConsume(Living $consumer){
	    $consumer->removeEffect(Effect::POISON);
	}
	public function getMaxStackSize(): int{
	    return 16;
	}
	public function requiresHunger(): bool{
	    return false;
	}
	public function getFoodRestore(): int{
	    return 6;
	}
	public function getSaturationRestore(): float{
	    return 1.2;
	}
}