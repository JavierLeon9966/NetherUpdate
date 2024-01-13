<?php
declare(strict_types=1);
namespace NetherUpdate\item;
use pocketmine\item\Hoe as PMHoe;
use pocketmine\entity\Entity;
use pocketmine\block\Block;
class Hoe extends PMHoe{
    use TieredTool;
	public function getAttackPoints() : int{
		return self::getBaseDamageFromTier($this->tier) - 2;
	}
	public function onDestroyBlock(Block $block) : bool{
		if($block->getHardness() > 0){
			return $this->applyDamage(1);
		}
		return false;
	}
	public function onAttackEntity(Entity $victim) : bool{
		return $this->applyDamage(1);
	}
}