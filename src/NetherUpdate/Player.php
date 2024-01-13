<?php
declare(strict_types = 1);
namespace NetherUpdate;
use NetherUpdate\inventory\PlayerOffhandInventory;
use NetherUpdate\event\player\{PlayerToggleGlideEvent, PlayerToggleSwimEvent};
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\{MobEquipmentPacket, PlayerActionPacket, ActorEventPacket, LevelEventPacket};
use pocketmine\item\enchantment\Enchantment;
use pocketmine\inventory\PlayerInventory;
use pocketmine\entity\{Entity, Living, Effect, EffectInstance, Attribute, Human};
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\{PlayerDeathEvent, PlayerExhaustEvent};
use pocketmine\Player as PMPlayer;
use pocketmine\item\Item;
class Player extends PMPlayer{
    protected $offhandInventory;
    public function doFirstSpawn(){
        if($this->spawned) return;
        parent::doFirstSpawn();
        $this->offhandInventory->sendContents($this);
    }
    public function getOffhandInventory(): PlayerOffhandInventory{
        return $this->offhandInventory;
    }
    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4) : void{
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
			$f = 1 / $f;

			$motion = clone $this->motion;
			
			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $base;
			$motion->y += $base;
			$motion->z += $z * $f * $base;

			if($motion->y > 0.4){
				$motion->y = 0.4;
			}

			$this->setMotion($motion);
		}
	}
    public function toggleGlide(bool $glide): void{
		$ev = new PlayerToggleGlideEvent($this, $glide);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
			return;
		}else{
		    $this->setGliding($glide);
		}
    }
    public function setGliding(bool $value = true): void{
		$this->setGenericFlag(Entity::DATA_FLAG_GLIDING, $value);
		$this->height = ($value ? 0.6 : 1.8) * $this->getScale();
        $this->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, $this->height);
        $this->setScale($this->getScale());
    }
    public function isGliding(): bool{
        return $this->getGenericFlag(Entity::DATA_FLAG_GLIDING);
    }
    public function toggleSwim(bool $swim): void{
		$ev = new PlayerToggleGlideEvent($this, $swim);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
			return;
		}else{
		    $this->setSwimming($swim);
		}
    }
    public function setSwimming(bool $value = true): void{
		$this->setGenericFlag(Entity::DATA_FLAG_SWIMMING, $value);
		$this->height = ($value ? 0.6 : 1.8) * $this->getScale();
        $this->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, $this->height);
        $this->setScale($this->getScale());
    }
    public function isSwimming(): bool{
        return $this->getGenericFlag(Entity::DATA_FLAG_SWIMMING);
    }
    public function handlePlayerAction(PlayerActionPacket $packet): bool{
        $parent = parent::handlePlayerAction($packet);
        switch($packet->action){
			case PlayerActionPacket::ACTION_START_GLIDE:
			    $this->toggleGlide(true);
				return true;
			case PlayerActionPacket::ACTION_STOP_GLIDE:
			    $this->toggleGlide(false);
			    return true;
			case PlayerActionPacket::ACTION_START_SWIMMING:
			    $this->toggleSwim(true);
			    return true;
			case PlayerActionPacket::ACTION_STOP_SWIMMING:
			    $this->toggleSwim(false);
			    return true;
        }
        return $parent;
    }
    public function handleMobEquipment(MobEquipmentPacket $packet): bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}
		$inventory = $this->getWindow($packet->windowId);
		
		if ($inventory === null) {
            $this->server->getLogger()->debug("Player {$this->getName()} has no open container with window ID $packet->windowId");
            return false;
        }
		$item = $inventory->getItem($packet->hotbarSlot);

		if(!$item->equals($packet->item)){
			$this->server->getLogger()->debug("Tried to equip $packet->item but have $item in target slot");
			$inventory->sendContents($this);
			return false;
		}
		if($inventory instanceof PlayerInventory){
		    $inventory->equipItem($packet->hotbarSlot);
        }

		$this->setUsingItem(false);

		return true;
    }
    public function applyDamageModifiers(EntityDamageEvent $source) : void{
		Living::applyDamageModifiers($source);

		$type = $source->getCause();
		if($type !== EntityDamageEvent::CAUSE_SUICIDE and $type !== EntityDamageEvent::CAUSE_VOID
			and $this->inventory->getItemInHand()->getId() == Item::TOTEM or $this->offhandInventory->getItem(0)->getId() == Item::TOTEM){

			$compensation = $this->getHealth() - $source->getFinalDamage() - 1;
			if($compensation < 0){
				$source->setModifier($compensation, EntityDamageEvent::MODIFIER_TOTEM);
			}
		}
	}

	protected function applyPostDamageEffects(EntityDamageEvent $source) : void{
		Living::applyPostDamageEffects($source);
		$totemModifier = $source->getModifier(EntityDamageEvent::MODIFIER_TOTEM);
		if($totemModifier < 0){ //Totem prevented death
			$this->removeAllEffects();

			$this->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 40 * 20, 1));
			$this->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 40 * 20));
			$this->addEffect(new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 5 * 20, 1));

			$this->broadcastEntityEvent(ActorEventPacket::CONSUME_TOTEM);
			$this->level->broadcastLevelEvent($this->add(0, $this->eyeHeight), LevelEventPacket::EVENT_SOUND_TOTEM);

			$hand = $this->offhandInventory->getItem(0);
			if($hand->getId() == Item::TOTEM){
				$hand->pop(); //Plugins could alter max stack size
				$this->offhandInventory->setItem(0, $hand);
			}else{
			    $hand = $this->inventory->getItemInHand();
			    if($hand->getId() == Item::TOTEM){
			        $hand->pop();
			        $this->inventory->setItemInHand($hand);
			    }
			}
		}
		$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_DAMAGE);
	}

	public function getDrops() : array{
		return array_filter(array_merge(
		    parent::getDrops(),
		    $this->offhandInventory !== null ? array_values($this->offhandInventory->getContents()) : []
		), function(Item $item) : bool{ return !$item->hasEnchantment(Enchantment::VANISHING); });
	}

	public function save(){
	    parent::save();
	    $inventoryTag = $this->namedtag->getListTag('Inventory');
	    if($this->offhandInventory !== null){
	        $item = $this->offhandInventory->getItem(0);
	        if(!$item->isNull()){
	            $inventoryTag->push($item->nbtSerialize(-106));
	        }
	    }
	}
	protected function sendSpawnPacket(PMPlayer $player) : void{
	    parent::sendSpawnPacket($player);
	    $this->offhandInventory->sendContents($player);
	}
    protected function initEntity(): void{
        Human::initEntity();
        $this->offhandInventory = new PlayerOffhandInventory($this);
        $inventoryTag = $this->namedtag->getListTag('Inventory');
        if($inventoryTag !== null){
            foreach($inventoryTag as $item){
                $slot = $item->getByte('Slot');
                if($slot == -106){
                    $this->offhandInventory->setItem(0, Item::nbtDeserialize($item));
                    break;
                }
            }
        }
        $this->addDefaultWindows();
    }
    protected function onDeath(): void{
		//Crafting grid must always be evacuated even if keep-inventory is true. This dumps the contents into the
		//main inventory and drops the rest on the ground.
		$this->doCloseInventory();

		$ev = new PlayerDeathEvent($this, $this->getDrops(), null, $this->getXpDropAmount());
		$ev->call();

		if(!$ev->getKeepInventory()){
			foreach($ev->getDrops() as $item){
				$this->level->dropItem($this, $item);
			}

			if($this->inventory !== null){
				$this->inventory->setHeldItemIndex(0);
				$this->inventory->clearAll();
			}
			if($this->armorInventory !== null){
				$this->armorInventory->clearAll();
			}
			if($this->offhandInventory !== null){
			    $this->offhandInventory->clearAll();
			}
		}

		$this->level->dropExperience($this, $ev->getXpDropAmount());
		$this->setXpAndProgress(0, 0);

		if($ev->getDeathMessage() != ""){
			$this->server->broadcastMessage($ev->getDeathMessage());
		}
	}
    protected function addDefaultWindows(){
        parent::addDefaultWindows();
        $this->addWindow($this->getOffhandInventory(), ContainerIds::OFFHAND, true);
    }
}