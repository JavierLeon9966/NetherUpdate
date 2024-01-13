<?php
declare(strict_types=1);

namespace NetherUpdate\item;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Potion as PMPotion;

class Potion extends PMPotion{
    public const TURTLE_MASTER = 37;
    public const LONG_TURTLE_MASTER = 38;
    public const STRONG_TURTLE_MASTER = 39;
    public const SLOW_FALLING = 40;
    public const LONG_SLOW_FALLING = 41;
    public const STRONG_SLOWNESS = 42;
	public static function getPotionEffectsById(int $id) : array{
		switch($id){
			case self::WATER:
			case self::MUNDANE:
			case self::LONG_MUNDANE:
			case self::THICK:
			case self::AWKWARD:
				return [];
			case self::NIGHT_VISION:
				return [
					new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 3600)
				];
			case self::LONG_NIGHT_VISION:
				return [
					new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 9600)
				];
			case self::INVISIBILITY:
				return [
					new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 3600)
				];
			case self::LONG_INVISIBILITY:
				return [
					new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 9600)
				];
			case self::LEAPING:
				return [
					new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 3600)
				];
			case self::LONG_LEAPING:
				return [
					new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 9600)
				];
			case self::STRONG_LEAPING:
				return [
					new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 1800, 1)
				];
			case self::FIRE_RESISTANCE:
				return [
					new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 3600)
				];
			case self::LONG_FIRE_RESISTANCE:
				return [
					new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 9600)
				];
			case self::SWIFTNESS:
				return [
					new EffectInstance(Effect::getEffect(Effect::SPEED), 3600)
				];
			case self::LONG_SWIFTNESS:
				return [
					new EffectInstance(Effect::getEffect(Effect::SPEED), 9600)
				];
			case self::STRONG_SWIFTNESS:
				return [
					new EffectInstance(Effect::getEffect(Effect::SPEED), 1800, 1)
				];
			case self::SLOWNESS:
				return [
					new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 1800)
				];
			case self::LONG_SLOWNESS:
				return [
					new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 4800)
				];
			case self::WATER_BREATHING:
				return [
					new EffectInstance(Effect::getEffect(Effect::WATER_BREATHING), 3600)
				];
			case self::LONG_WATER_BREATHING:
				return [
					new EffectInstance(Effect::getEffect(Effect::WATER_BREATHING), 9600)
				];
			case self::HEALING:
				return [
					new EffectInstance(Effect::getEffect(Effect::INSTANT_HEALTH))
				];
			case self::STRONG_HEALING:
				return [
					new EffectInstance(Effect::getEffect(Effect::INSTANT_HEALTH), null, 1)
				];
			case self::HARMING:
				return [
					new EffectInstance(Effect::getEffect(Effect::INSTANT_DAMAGE))
				];
			case self::STRONG_HARMING:
				return [
					new EffectInstance(Effect::getEffect(Effect::INSTANT_DAMAGE), null, 1)
				];
			case self::POISON:
				return [
					new EffectInstance(Effect::getEffect(Effect::POISON), 900)
				];
			case self::LONG_POISON:
				return [
					new EffectInstance(Effect::getEffect(Effect::POISON), 2400)
				];
			case self::STRONG_POISON:
				return [
					new EffectInstance(Effect::getEffect(Effect::POISON), 440, 1)
				];
			case self::REGENERATION:
				return [
					new EffectInstance(Effect::getEffect(Effect::REGENERATION), 900)
				];
			case self::LONG_REGENERATION:
				return [
					new EffectInstance(Effect::getEffect(Effect::REGENERATION), 2400)
				];
			case self::STRONG_REGENERATION:
				return [
					new EffectInstance(Effect::getEffect(Effect::REGENERATION), 440, 1)
				];
			case self::STRENGTH:
				return [
					new EffectInstance(Effect::getEffect(Effect::STRENGTH), 3600)
				];
			case self::LONG_STRENGTH:
				return [
					new EffectInstance(Effect::getEffect(Effect::STRENGTH), 9600)
				];
			case self::STRONG_STRENGTH:
				return [
					new EffectInstance(Effect::getEffect(Effect::STRENGTH), 1800, 1)
				];
			case self::WEAKNESS:
				return [
					new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 1800)
				];
			case self::LONG_WEAKNESS:
				return [
					new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 4800)
				];
			case self::WITHER:
				return [
					new EffectInstance(Effect::getEffect(Effect::WITHER), 800, 1)
				];
			case self::TURTLE_MASTER:
				return [
					new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 400, 3),
					new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 400, 2)
				];
			case self::LONG_TURTLE_MASTER:
				return [
					new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 800, 3),
					new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 800, 2)
				];
			case self::STRONG_TURTLE_MASTER:
				return [
					new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 400, 5),
					new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 400, 3)
				];
			case self::SLOW_FALLING:
			    return [
			        new EffectInstance(Effect::getEffect(27), 1800)
			    ];
			case self::LONG_SLOW_FALLING:
			    return [
			        new EffectInstance(Effect::getEffect(27), 4800)
			    ];
			case self::STRONG_SLOWNESS:
			    return [
			        new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 400, 3)
			    ];
		}

		return [];
	}
	public function getAdditionalEffects(): array{
		return self::getPotionEffectsById($this->meta);
	}
}