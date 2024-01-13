<?php
declare(strict_types = 1);
namespace NetherUpdate;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\math\Vector3;
use pocketmine\tile\Spawnable;
use pocketmine\item\{Consumable, MaybeConsumable};
use pocketmine\event\player\{PlayerInteractEvent, PlayerItemConsumeEvent};
class OffhandListener implements Listener{
    /**
     * ignoreCancelled true
     * priority MONITOR
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event){
        $player = $event->getPlayer();
        if(!$player->spawned) return;
        $level = $player->getLevel();
        $packet = $event->getPacket();
        $offhand = Utils::getOffhandInventory($player);
        if($packet instanceof InventoryTransactionPacket){
            switch($packet->transactionType){
                case InventoryTransactionPacket::TYPE_USE_ITEM:
                    $blockVector = new Vector3($packet->trData->x, $packet->trData->y, $packet->trData->z);
                    $face = $packet->trData->face;
                    switch($packet->trData->actionType){
                        case InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_BLOCK:
                            //TODO: start hack for client spam bug
                            static $lastRightClickPos = [];
                            static $lastRightClickTime = [];
						    $spamBug = (($lastRightClickPos[spl_object_hash($player)] ?? null) !== null and
							    microtime(true) - ($lastRightClickTime[spl_object_hash($player)] ?? 0.0) < 0.1 and //100ms
							    $lastRightClickPos->distanceSquared($packet->trData->clickPos) < 0.00001 //signature spam bug has 0 distance, but allow some error
						    );
						    //get rid of continued spam if the player clicks and holds right-click
						    $lastRightClickPos[spl_object_hash($player)] = clone $packet->trData->clickPos;
						    $lastRightClickTime[spl_object_hash($player)] = microtime(true);
						    if($spamBug){
							    $event->setCancelled();
							    return;
						    }
						    //TODO: end hack for client spam bug
						    
                            $player->setUsingItem(false);
                            if(!$player->canInteract($blockVector->add(0.5, 0.5, 0.5), 13)){
						    }elseif($player->isCreative()){
							    $item = $player->getInventory()->getItemInHand();
							    if($player->getLevel()->useItemOn($blockVector, $item, $face, $packet->trData->clickPos, $player, true)){
							        $event->setCancelled();
								    return;
							    }
						    }elseif(!$player->getInventory()->getItemInHand()->equals($packet->trData->itemInHand)){
							    $player->getInventory()->sendHeldItem($player);
						    }else{
							    $item = $player->getInventory()->getItemInHand();
							    $oldItem = clone $item;
							    if($player->getLevel()->useItemOn($blockVector, $item, $face, $packet->trData->clickPos, $player, true)){
								    if(!$item->equalsExact($oldItem) and $oldItem->equalsExact($player->getInventory()->getItemInHand())){
									    $player->getInventory()->setItemInHand($item);
									    $player->getInventory()->sendHeldItem($player->getViewers());
								    }
								    $event->setCancelled();
								    return;
							    }
						    }
						    $player->getInventory()->sendHeldItem($player);
						    //offhand
						    if(!$player->canInteract($blockVector->add(0.5, 0.5, 0.5), 13)){
						    }elseif($player->isCreative()){
							    $item = $offhand->getItem(0);
							    if($level->useItemOn($blockVector, $item, $face, $packet->trData->clickPos, $player, true)){
								    $event->setCancelled();
								    return;
							    }
						    }else{
							    $item = $offhand->getItem(0);
							    $oldItem = clone $item;
							    if($level->useItemOn($blockVector, $item, $face, $packet->trData->clickPos, $player, true)){
								    if(!$item->equalsExact($oldItem) and $oldItem->equalsExact($offhand->getItem(0))){
									    $offhand->setItem(0, $item);
								    }

								    $event->setCancelled();
								    return;
							    }
						    }

						    $offhand->sendContents($player);

						    if($blockVector->distanceSquared($player) > 10000){
							    return;
						    }

						    $target = $player->getLevel()->getBlock($blockVector);
						    $block = $target->getSide($face);

						    /** @var Block[] $blocks */
						    $blocks = array_merge($target->getAllSides(), $block->getAllSides()); //getAllSides() on each of these will include $target and $block because they are next to each other

						    $player->getLevel()->sendBlocks([$player], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);
						    foreach($blocks as $b){
							    $tile = $player->getLevel()->getTile($b);
							    if($tile instanceof Spawnable){
								    $tile->spawnTo($player);
							    }
						    }
						    $event->setCancelled();
                            break;
                        case InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_AIR:
                            if($player->isUsingItem()){
							    $slot = $player->getInventory()->getItemInHand();
							    if($slot instanceof Consumable and !($slot instanceof MaybeConsumable and !$slot->canBeConsumed())){
								    $ev = new PlayerItemConsumeEvent($player, $slot);
								    if($player->hasItemCooldown($slot)){
									    $ev->setCancelled();
								    }
								    $ev->call();
								    if($ev->isCancelled() or !$player->consumeObject($slot)){
									    $player->getInventory()->sendContents($player);
									    goto offhand:
								    }
								    $player->resetItemCooldown($slot);
								    if($player->isSurvival()){
									    $slot->pop();
									    $player->getInventory()->setItemInHand($slot);
									    $player->getInventory()->addItem($slot->getResidue());
								    }
								    $player->setUsingItem(false);
							    }
						    }
						    $directionVector = $player->getDirectionVector();

						    if($player->isCreative()){
							    $item = $player->getInventory()->getItemInHand();
						    }elseif(!$player->getInventory()->getItemInHand()->equals($packet->trData->itemInHand)){
							    $player->getInventory()->sendHeldItem($player);
							    goto offhand:
						    }else{
							    $item = $player->getInventory()->getItemInHand();
						    }

						    $ev = new PlayerInteractEvent($player, $item, null, $directionVector, $face, PlayerInteractEvent::RIGHT_CLICK_AIR);
						    if($player->hasItemCooldown($item) or $player->isSpectator()){
							    $ev->setCancelled();
						    }

						    $ev->call();
						    if($ev->isCancelled()){
							    $player->getInventory()->sendHeldItem($player);
							    goto offhand:
						    }

						    if($item->onClickAir($player, $directionVector)){
							    $player->resetItemCooldown($item);
							    if($player->isSurvival()){
								    $player->getInventory()->setItemInHand($item);
							    }
						    }
						    $player->setUsingItem(true);
						    $event->setCancelled();
						    break;

						    offhand:
                            if($player->isUsingItem()){
							    $slot = $offhand->getItem(0);
							    if($slot instanceof Consumable and !($slot instanceof MaybeConsumable and !$slot->canBeConsumed())){
								    $ev = new PlayerItemConsumeEvent($player, $slot);
								    if($player->hasItemCooldown($slot)){
									    $ev->setCancelled();
								    }
								    $ev->call();
								    if($ev->isCancelled() or !$player->consumeObject($slot)){
									    $offhand->sendContents($player);
									    $event->setCancelled();
									    return;
								    }
								    $player->resetItemCooldown($slot);
								    if($player->isSurvival()){
									    $slot->pop();
									    $offhand->setItem(0, $slot);
									    $player->getInventory()->addItem($slot->getResidue());
								    }
								    $player->setUsingItem(false);
							    }
						    }
						    $directionVector = $player->getDirectionVector();

						    $item = $offhand->getItem(0);

						    $ev = new PlayerInteractEvent($player, $item, null, $directionVector, $face, PlayerInteractEvent::RIGHT_CLICK_AIR);
					        if($player->hasItemCooldown($item) or $player->isSpectator()){
							    $ev->setCancelled();
						    }

						    $ev->call();
						    if($ev->isCancelled()){
							    $offhand->sendContents($player);
							    $event->setCancelled();
							    return;
						    }

						    if($item->onClickAir($player, $directionVector)){
							    $player->resetItemCooldown($item);
							    if($player->isSurvival()){
								    $offhand->setItem(0, $item);
							    }
						    }

						    $player->setUsingItem(true);

						    $event->setCancelled();
                            break;
                    }
                    break;
                case InventoryTransactionPacket::TYPE_RELEASE_ITEM:
                    switch($packet->trData->actionType){
                        case InventoryTransactionPacket::RELEASE_ITEM_ACTION_RELEASE:
                            break;
                    }
                    break;
            }
        }
    }
}