<?php
declare(strict_types = 1);
namespace NetherUpdate\entity\projectile;
use pocketmine\entity\projectile\SplashPotion as PMSP;
use NetherUpdate\item\Potion;
class SplashPotion extends PMSP{
    public function getPotionEffects(): array{
		return Potion::getPotionEffectsById($this->getPotionId());
	}
}