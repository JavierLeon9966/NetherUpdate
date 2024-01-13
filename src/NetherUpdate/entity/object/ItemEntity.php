<?php
namespace NetherUpdate\entity\object;
use pocketmine\entity\object\ItemEntity as OldItem;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use NetherUpdate\{Utils, Player as ModPlayer};
use NetherUpdate\inventory\OffhandInventory;
class ItemEntity extends OldItem{
    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if($this->getItem()->getId() == Item::NETHER_STAR){
            $this->age = 0;
        }
        return $hasUpdate;
    }
    public function attack(EntityDamageEvent $source) : void{
        if(!in_array($source->getCause(), [9, 10])){
            parent::attack($source);
        }
	}
    public function onCollideWithPlayer(Player $player) : void{
		if($this->getPickupDelay() !== 0){
			return;
		}

		$item = $this->getItem();
		$offhand = $player instanceof ModPlayer ? $player->getOffhandInventory() : Utils::getOffhandInventory($player);
		$default = $player->getInventory();
		$clone = clone $item;
		$clone->setCount(1);
		if($offhand->first($clone) > -1 and $offhand->getItem(0)->getCount() < $offhand->getItem(0)->getMaxStackSize()){
		    $inventory = $offhand;
		}else{
		    $canAdd = false;
		    foreach($default->all($item) as $index => $each){
		        if($each->getCount() < $each->getMaxStackSize()){
		            $canAdd = true;
		            break;
		        }
		    }
		    if($player->isCreative() or $default->canAddItem($item) or $canAdd){
			    $inventory = $default;
		    }
		}
		if(empty($inventory)) return;
		$ev = new InventoryPickupItemEvent($inventory, $this);
		$ev->call();
		if($ev->isCancelled()){
			return;
		}

		switch($item->getId()){
			case Item::WOOD:
				$player->awardAchievement("mineWood");
				break;
			case Item::DIAMOND:
				$player->awardAchievement("diamond");
				break;
		}

		$pk = new TakeItemActorPacket();
		$pk->eid = $player->getId();
		$pk->target = $this->getId();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		$drops = $inventory->addItem(clone $item);
		if($player->isSurvival()){
		    foreach($drops as $drop) $player->dropItem($drop);
		}
		$this->flagForDespawn();
	}
}