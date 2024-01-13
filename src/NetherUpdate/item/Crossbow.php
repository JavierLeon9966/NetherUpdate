<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\math\Vector3;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\network\mcpe\protocol\{LevelSoundEventPacket, ActorEventPacket};
use pocketmine\Player;
use pocketmine\entity\projectile\{Arrow, Projectile};
use pocketmine\entity\Entity;
use pocketmine\item\{Tool, Item};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\enchantment\Enchantment;
use NetherUpdate\event\entity\EntityShootCrossbowEvent;
use NetherUpdate\Utils;
class Crossbow extends Tool{
    public function __construct(int $meta = 0){
        parent::__construct(Item::CROSSBOW, $meta, "Crossbow");
    }
    public function getMaxDurability(): int{
        return 465;
    }
    public function getFuelTime(): int{
        return 30;
    }
    public function onClickAir(Player $player, Vector3 $directionVector) : bool{
        $loadingDuration = 25 - 5 * $this->getEnchantmentLevel(Enchantment::QUICK_CHARGE);
        $instant = $loadingDuration == 0;
        if($this->hasChargedItem()){
            $arrow = $this->getChargedItem();
    	    $nbt = Entity::createBaseNBT(
		        $player->add(0, $player->getEyeHeight()),
	        	$directionVector,
        		($player->yaw > 180 ? 360 : 0) - $player->yaw,
        		-$player->pitch
        	);
        	$nbt->setInt("piercing", $this->getEnchantmentLevel(Enchantment::PIERCING));
		    if($arrow->getDamage() >= 1 && $arrow->getDamage() <= 43){
		    	$nbt->setShort("Potion", $arrow->getDamage() - 1);
	        }
    	    $entity = Entity::createEntity("Arrow", $player->getLevelNonNull(), $nbt, $player, true);
	        if($entity instanceof Projectile){
	    	    $ev = new EntityShootCrossbowEvent($player, $this, $entity);
	    	    $ev->call();
	    	    $entity = $ev->getProjectile(); //This might have been changed by plugins
	    	    if(!$ev->isCancelled()){
    		        $this->removeChargedItem();
    		        if($player->isCreative()) $player->getInventory()->setItemInHand($this);
	    	    }else{
		            $entity->flagForDespawn();
		            $player->getInventory()->sendContents($player);
		            $player->getOffhandInventory()->sendContents($player);
		            return true;
		        }
	        	if($this->hasEnchantment(Enchantment::MULTISHOT)){// PiggyCustomEnchants Volley Format
	            	$amount = 3;
                    $anglesBetweenArrows = (45 / ($amount - 1)) * M_PI / 180;
                    $pitch = ($player->pitch + 90) * M_PI / 180;
                    $yaw = ($player->yaw + 90 - 45 / 2) * M_PI / 180;
                    for($i = 0; $i < $amount; $i++){
                        $nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight()), $directionVector, $player->yaw, $player->pitch);
		                if($arrow->getDamage() >= 1 && $arrow->getDamage() <= 43){
		                	$nbt->setShort("Potion", $arrow->getDamage() - 1);
	                	}
                        $newProjectile = Entity::createEntity("Arrow", $player->getLevel(), $nbt, $player, true);
                        if($newProjectile instanceof Arrow and $i != 1) $newProjectile->setPickupMode(Arrow::PICKUP_NONE);
                        $newDirection = new Vector3(sin($pitch) * cos($yaw + $anglesBetweenArrows * $i), cos($pitch), sin($pitch) * sin($yaw + $anglesBetweenArrows * $i));
	        	        $newProjectile->spawnToAll();
                        $newProjectile->setMotion($newDirection->normalize()->multiply($entity->getMotion()->multiply(5)->length()));
    	        	}
    	        	$entity->close();
	            }else{
	                $entity->setMotion($entity->getMotion()->multiply(5));
		        	if($entity instanceof Projectile){
		    	    	$projectileEv = new ProjectileLaunchEvent($entity);
		    	    	$projectileEv->call();
		    	    	if($projectileEv->isCancelled()) $ev->getProjectile()->flagForDespawn();
		    	    	else $ev->getProjectile()->spawnToAll();
		        	}else $entity->spawnToAll();
	        	}
	       	}else $entity->spawnToAll();
	        $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_SHOOT);
		}else{
    		$arrow = Item::get(Item::ARROW, -1, 1);
    		$offhand = $player->getOffhandInventory();
    		$inventory = $player->getInventory();
    		if(!($offhand->contains($arrow) or $inventory->contains($arrow)) and $player->isSurvival()){
    		    $inventory->sendContents($player);
    		    $offhand->sendContents($player);
    		    return false;
    		}
		    $ticks = $player->getItemUseDuration();
		    if(!$instant){
		        if($ticks <= 1){
		            $player->getLevel()->broadcastLevelSoundEvent($player, $this->hasEnchantment(Enchantment::QUICK_CHARGE) ? LevelSoundEventPacket::SOUND_CROSSBOW_QUICK_CHARGE_START : LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_START);
		            return false;
		        }elseif($ticks < $loadingDuration){
		            $player->getLevel()->broadcastLevelSoundEvent($player, $this->hasEnchantment(Enchantment::QUICK_CHARGE) ? LevelSoundEventPacket::SOUND_CROSSBOW_QUICK_CHARGE_MIDDLE : LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_MIDDLE);
		            return false;
		        }
		    }
		    $player->getLevel()->broadcastLevelSoundEvent($player, $this->hasEnchantment(Enchantment::QUICK_CHARGE) ? LevelSoundEventPacket::SOUND_CROSSBOW_QUICK_CHARGE_END : LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_END);
		    $player->broadcastEntityEvent(ActorEventPacket::CHARGED_CROSSBOW);
		    if($offhand->contains($arrow)){
		        $arrow = $offhand->getItemInOffhand();
		    }else{
	    	    if($inventory->first(Item::get(Item::ARROW, -1, 1)) > -1){
	    	        $arrow = $inventory->getItem($inventory->first(Item::get(Item::ARROW, -1, 1)));
    		    }else $arrow = Item::get(Item::ARROW, 0, 1);
		    }
		    if($player->isSurvival()) $this->applyDamage($this->hasEnchantment(Enchantment::MULTISHOT) ? 3 : 1);
		    if(!$this->isBroken()){
	    	    if($player->isSurvival()){
	    	        if($offhand->contains($arrow)){
	    	            $arrow->pop();
	    	            $offhand->setItemInOffhand($arrow);
	    	        }else{
	    	            $inventory->removeItem($arrow->pop());
	    	        }
	    	    }
	    	    $this->setChargedItem($arrow);
	    	    if($player->isCreative()) $inventory->setItemInHand($this);
		    }
		}
		return true;
	}
	public function hasChargedItem(): bool{
	    return $this->getNamedTag()->hasTag("chargedItem", CompoundTag::class);
	}
	public function getChargedItem(): Item{
	    return Item::nbtDeserialize($this->getNamedTagEntry("chargedItem"));
	}
	public function setChargedItem(Item $item): void{
	    $this->setNamedTagEntry($item->nbtSerialize(-1, "chargedItem"));
	}
	public function removeChargedItem(): void{
	    $this->removeNamedTagEntry("chargedItem");
	}
}