<?php
declare(strict_types = 1);
namespace NetherUpdate;
use pocketmine\item\Item;
use pocketmine\{Server, Player};
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\entity\{Entity, Human};
use pocketmine\scheduler\ClosureTask;
use NetherUpdate\block\BlockIds;
use NetherUpdate\item\{ItemIds, Shield};
use NetherUpdate\tile\Lodestone;
use NetherUpdate\inventory\PlayerOffhandInventory;
use NetherUpdate\Player as ModPlayer;
final class Utils{
    private static $cooldowns = [];
    public static function isNetheriteArmor(Item $item): bool{
        return in_array($item->getId(), [ItemIds::NETHERITE_HELMET, ItemIds::NETHERITE_CHESTPLATE, ItemIds::NETHERITE_LEGGINGS, ItemIds::NETHERITE_BOOTS]);
    }
    public static function isNetheriteType(Item $item): bool{
        return self::isNetheriteArmor($item) or in_array($item->getId(), [ItemIds::NETHERITE_SWORD, ItemIds::NETHERITE_AXE, ItemIds::NETHERITE_SHOVEL, ItemIds::NETHERITE_HOE, ItemIds::NETHERITE_PICKAXE, ItemIds::NETHERITE_INGOT, ItemIds::NETHERITE_SCRAP, 255 - BlockIds::NETHERITE_BLOCK, 255 - BlockIds::ANCIENT_DEBRIS]);
    }
    public static function searchLodestone(int $trackingHandle): ?Lodestone{
        foreach(Server::getInstance()->getLevels() as $level){
            foreach($level->getTiles() as $tile){
                if($tile instanceof Lodestone){
                    if($tile->getTrackingHandle() == $trackingHandle){
                        return $tile;
                    }
                }
            }
        }
        return null;
    }
    public static function canInteract(Entity $entity, Vector3 $pos, float $maxDistance, float $maxDiff = M_SQRT3 / 2): bool{
		$eyePos = $entity->getPosition()->add(0, $entity->getEyeHeight(), 0);
		if($eyePos->distanceSquared($pos) > $maxDistance ** 2){
			return false;
		}

		$dV = $entity->getDirectionVector();
		$eyeDot = $dV->dot($eyePos);
		$targetDot = $dV->dot($pos);
		return ($targetDot - $eyeDot) >= -$maxDiff;
	}
    public static function getOffhandInventory(Human $player): PlayerOffhandInventory{
        static $players = [];
        $UUID = ''.$player->getUniqueId();
        $inventory = $players[$UUID] = $players[$UUID] ?? new PlayerOffhandInventory($player);
        if($player instanceof Player and !$player instanceof ModPlayer){
            $player->addWindow($inventory, ContainerIds::OFFHAND, true);
        }
        return $inventory;
    }
    public static function isCauseFire(int $cause): bool{
        return in_array($cause, [5, 6, 7]);
    }
    public static function setProperty($object, string $property, $value): void{
        $property = new \ReflectionProperty($object, $property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
    public static function getProperty($object, string $property){
        $property = new \ReflectionProperty($object, $property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
    public static function invoke($object, string $method, ...$args){
        $method = new \ReflectionMethod($object, $method);
        $method->setAccessible(true);
        return $method->invoke($method->isStatic() ? null : (is_object($object) ? $object : (new \ReflectionClass($object))->newInstanceWithoutConstructor()), ...$args);
    }
    public static function getShieldCooldown(Human $player): int{
        return self::$cooldowns[spl_object_hash($player)] ?? 0;
    }
    public static function setShieldCooldown(Human $player, int $cooldown = 0): void{
        self::$cooldowns[spl_object_hash($player)] = $cooldown;
        if($cooldown <= 0) return;
        $player->setGenericFlag(Entity::DATA_FLAG_BLOCKING, false);
        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($player): void{
            $player->setGenericFlag(Entity::DATA_FLAG_BLOCKING, $player->isSneaking());
            self::setShieldCooldown($player);
        }), $cooldown);
    }
}