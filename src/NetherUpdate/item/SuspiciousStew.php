<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Food;
use pocketmine\entity\{Effect, EffectInstance};
class SuspiciousStew extends Food{
    public function __construct(int $meta = 0){
		parent::__construct(ItemIds::SUSPICIOUS_STEW, $meta, "Suspicious Stew");
	}
	public function getMaxStackSize() : int{
		return 1;
	}
	public function getFoodRestore() : int{
		return 6;
	}
	public function getSaturationRestore() : float{
		return 7.2;
	}
	public function getResidue(){
		return ItemFactory::get(self::BOWL);
	}
	public function getAdditionalEffects(): array{
	   switch($this->meta){
	       case 0:
	           return [new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 80)];
	       case 1:
	           return [new EffectInstance(Effect::getEffect(Effect::JUMP), 80)];
	       case 2:
	           return [new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 140)];
	       case 3:
	           return [new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 120)];
	       case 4:
	           return [new EffectInstance(Effect::getEffect(Effect::POISON), 200)];
	       case 6:
	           return [new EffectInstance(Effect::getEffect(Effect::SATURATION))];
	       case 7:
	           return [new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 40)];
	       case 8:
	           return [new EffectInstance(Effect::getEffect(Effect::REGENERATION), 120)];
	       case 9:
	           return [new EffectInstance(Effect::getEffect(Effect::WITHER), 120)];
	   }
	   return [];
	}
}