<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow as ArrowEntity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\{Item, ItemFactory, Bow as PMBow};
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use NetherUpdate\Utils;
class Bow extends PMBow{
    public function onReleaseUsing(Player $player): bool{
        $offhand = $player->getOffhandInventory();
        $inventory = $player->getInventory();
		if(!($offhand->contains(Item::get(Item::ARROW, -1, 1)) or $inventory->contains(Item::get(Item::ARROW, -1, 1))) and $player->isSurvival()){
			$inventory->sendContents($player);
			$offhand->sendContents($player);
			return false;
		}
		if($offhand->contains(Item::get(Item::ARROW, -1, 1))){
		    $arrow = $offhand->getItemInOffhand();
		}else{
		    if($inventory->first(Item::get(Item::ARROW, -1, 1)) > -1){
		        $arrow = $inventory->getItem($inventory->first(Item::get(Item::ARROW, -1, 1)));
		    }else $arrow = Item::get(Item::ARROW, 0, 1);
		}
		$nbt = Entity::createBaseNBT(
			$player->add(0, $player->getEyeHeight(), 0),
			$player->getDirectionVector(),
			($player->yaw > 180 ? 360 : 0) - $player->yaw,
			-$player->pitch
		);
		if($arrow->getDamage() >= 1 && $arrow->getDamage() <= 43){
			$nbt->setShort("Potion", $arrow->getDamage() - 1);
		}

		$diff = $player->getItemUseDuration();
		$p = $diff / 20;
		$baseForce = min((($p ** 2) + $p * 4) / 5, 1);

		$entity = Entity::createEntity("Arrow", $player->getLevelNonNull(), $nbt, $player, $baseForce >= 1);
		if($entity instanceof Projectile){
			$infinity = $this->hasEnchantment(Enchantment::INFINITY);
			if($entity instanceof ArrowEntity){
				if($infinity and $arrow->getDamage() == 0){
					$entity->setPickupMode(ArrowEntity::PICKUP_CREATIVE);
				}
				if(($punchLevel = $this->getEnchantmentLevel(Enchantment::PUNCH)) > 0){
					$entity->setPunchKnockback($punchLevel);
				}
			}
			if(($powerLevel = $this->getEnchantmentLevel(Enchantment::POWER)) > 0){
				$entity->setBaseDamage($entity->getBaseDamage() + (($powerLevel + 1) / 2));
			}
			if($this->hasEnchantment(Enchantment::FLAME)){
				$entity->setOnFire(intdiv($entity->getFireTicks(), 20) + 100);
			}
			$ev = new EntityShootBowEvent($player, $this, $entity, $baseForce * 5);

			if($baseForce < 0.1 or $diff < 5 or $player->isSpectator()){
				$ev->setCancelled();
			}

			$ev->call();

			$entity = $ev->getProjectile(); //This might have been changed by plugins

			if($ev->isCancelled()){
				$entity->flagForDespawn();
				$inventory->sendContents($player);
				$offhand->sendContents($player);
			}else{
				$entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
				if($player->isSurvival()){
					if(!$infinity or $arrow->getDamage() > 0){
					    if($offhand->contains($arrow)){
					        $arrow->pop();
					        $offhand->setItemInOffhand($arrow);
					    }else{
						    $inventory->removeItem($arrow->pop());
					    }
					}
					$this->applyDamage(1);
				}

				if($entity instanceof Projectile){
					$projectileEv = new ProjectileLaunchEvent($entity);
					$projectileEv->call();
					if($projectileEv->isCancelled()){
						$ev->getProjectile()->flagForDespawn();
					}else{
						$ev->getProjectile()->spawnToAll();
						$player->getLevelNonNull()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_BOW);
					}
				}else $entity->spawnToAll();
			}
		}else $entity->spawnToAll();

		return true;
    }
}