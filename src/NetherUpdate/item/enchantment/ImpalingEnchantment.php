<?php
declare(strict_types=1);
namespace NetherUpdate\item\enchantment;
use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
class ImpalingEnchantment extends MeleeWeaponEnchantment{

	public function isApplicableTo(Entity $victim) : bool{
		return $victim->isUnderwater() or $victim->getLevel()->getBlock($victim) instanceof Water;
	}

	public function getDamageBonus(int $enchantmentLevel): float{
		return 2.5*$enchantmentLevel;
	}
}