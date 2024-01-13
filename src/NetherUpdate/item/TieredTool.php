<?php
declare(strict_types=1);
namespace NetherUpdate\item;
trait TieredTool{
    public function getMaxDurability(): int{
        return self::getDurabilityFromTier($this->tier);
    }
    public function getBaseMiningEfficiency(): float{
        return self::getBaseMiningEfficiencyFromTier($this->tier);
    }
    public function getAttackPoints(): int{
        return self::getBaseDamageFromTier($this->tier);
    }
    public static function getDurabilityFromTier(int $tier) : int{
		$levels = [
			self::TIER_GOLD => 33,
			self::TIER_WOODEN => 60,
			self::TIER_STONE => 132,
			self::TIER_IRON => 251,
			self::TIER_DIAMOND => 1562,
			6 => 2032
		];
		if(empty($levels[$tier])){
			throw new \InvalidArgumentException("Unknown tier '$tier'");
		}
		return $levels[$tier];
	}
	protected static function getBaseDamageFromTier(int $tier) : int{
		$levels = [
			self::TIER_WOODEN => 5,
			self::TIER_GOLD => 5,
			self::TIER_STONE => 6,
			self::TIER_IRON => 7,
			self::TIER_DIAMOND => 8,
		    6 => 9
		];
		if(empty($levels[$tier])){
			throw new \InvalidArgumentException("Unknown tier '$tier'");
		}
		return $levels[$tier];
	}
	public static function getBaseMiningEfficiencyFromTier(int $tier) : float{
		$levels = [
			self::TIER_WOODEN => 2,
			self::TIER_STONE => 4,
			self::TIER_IRON => 6,
			self::TIER_DIAMOND => 8,
			6 => 9,
			self::TIER_GOLD => 12
		];
		if(empty($levels[$tier])){
			throw new \InvalidArgumentException("Unknown tier '$tier'");
		}
		return $levels[$tier];
	}
}