<?php
declare(strict_types = 1);
namespace NetherUpdate;
use pocketmine\network\mcpe\protocol\{MobEquipmentPacket, AddPlayerPacket, AnimatePacket, LevelSoundEventPacket, PlayerActionPacket, LevelChunkPacket, UpdateBlockPacket, ActorEventPacket, LevelEventPacket, NetworkChunkPublisherUpdatePacket, LoginPacket, ProtocolInfo};
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\event\server\{DataPacketReceiveEvent, DataPacketSendEvent};
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent, EntityDamageByChildEntityEvent, EntityInventoryChangeEvent, EntityCombustByBlockEvent, EntityDamageByBlockEvent, EntityDespawnEvent, EntitySpawnEvent};
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerLoginEvent, PlayerJoinEvent, PlayerExhaustEvent, PlayerToggleSneakEvent, PlayerDeathEvent, PlayerAnimationEvent, PlayerQuitEvent, PlayerMoveEvent, PlayerCreationEvent};
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
use pocketmine\event\level\LevelSaveEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\entity\{Entity, Living, Human, Effect, EffectInstance, Attribute};
use pocketmine\entity\object\ItemEntity;
use pocketmine\Player;
use pocketmine\block\{Block, BlockFactory, Air, SoulSand};
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\{ItemBlock, Item, Axe};
use pocketmine\tile\Container;
use pocketmine\nbt\tag\ListTag;
use pocketmine\scheduler\ClosureTask;
use NetherUpdate\entity\projectile\Arrow;
use NetherUpdate\block\{BlockIds, Placeholder, HoneyBlock, SoulSoil};
use NetherUpdate\tile\PlaceholderInterface;
use NetherUpdate\item\{ItemFactory, Shield, Totem, Elytra};
use NetherUpdate\event\player\{PlayerToggleGlideEvent, PlayerToggleSwimEvent};
use NetherUpdate\Player as ModPlayer;
class EventListener implements Listener{
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $level = $player->getLevel();
        if($player instanceof ModPlayer) return;
        if($packet instanceof MobEquipmentPacket and $packet->windowId == ContainerIds::OFFHAND){
            $offhand = Utils::getOffhandInventory($player);
            if(!$offhand->getItemInOffhand()->equalsExact($packet->item)){
                $offhand->sendContents($player);
                $event->setCancelled();
                return;
            }
            $offhand->setItemInOffhand($packet->item);
        }elseif($packet instanceof PlayerActionPacket){
            switch($packet->action){
				case PlayerActionPacket::ACTION_START_GLIDE:
				    $ev = new PlayerToggleGlideEvent($player, true);
				    $ev->call();
				    if($ev->isCancelled()){
				        $player->sendData($player);
				        return;
				    }
				    $player->setGenericFlag(Entity::DATA_FLAG_GLIDING, true);
					$player->height = 0.6 * $player->getScale();
					break;
				case PlayerActionPacket::ACTION_STOP_GLIDE:
				    $ev = new PlayerToggleGlideEvent($player, false);
				    $ev->call();
				    if($ev->isCancelled()){
				        $player->sendData($player);
				        return;
				    }
					$player->setGenericFlag(Entity::DATA_FLAG_GLIDING, false);
					$player->height = 1.8 * $player->getScale();
					break;
				case PlayerActionPacket::ACTION_START_SWIMMING:
				    $ev = new PlayerToggleSwimEvent($player, true);
				    $ev->call();
				    if($ev->isCancelled()){
				        $player->sendData($player);
				        return;
				    }
					$player->setGenericFlag(Entity::DATA_FLAG_SWIMMING, true);
					$player->height = 0.6 * $player->getScale();
					break;
				case PlayerActionPacket::ACTION_STOP_SWIMMING:
				    $ev = new PlayerToggleSwimEvent($player, false);
				    $ev->call();
				    if($ev->isCancelled()){
				        $player->sendData($player);
				        return;
				    }
					$player->setGenericFlag(Entity::DATA_FLAG_SWIMMING, false);
					$player->height = 1.8 * $player->getScale();
					break;
				default:
				    return;
            }
            $player->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, $player->height);
            $player->setScale($player->getScale());
        }elseif($packet instanceof LoginPacket){
            if($packet->protocol >= 419){
                $packet->protocol = ProtocolInfo::CURRENT_PROTOCOL;
            }
        }
    }
    /**
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onDataPacketSend(DataPacketSendEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $level = $player->getLevel();
        if($packet instanceof AddPlayerPacket and !$player instanceof ModPlayer){
            Utils::getOffhandInventory($player->getServer()->getPlayerExact($packet->username) ?? $player)->sendContents($player);
        }elseif($packet instanceof LevelChunkPacket){
            Main::getInstance()->getScheduler()->scheduleRepeatingTask($task = new ClosureTask(static function() use($packet, $player, $level): void{
                foreach($level->getChunkTiles($packet->getChunkX(), $packet->getChunkZ()) as $tile){
                    if($tile instanceof PlaceholderInterface){
                        $placeholder = $tile->getBlock();
                        $block = $tile->getBlock(true);
                        if($block->isValid()){
                            if($placeholder instanceof Placeholder){
                                $level->sendBlocks([$player], [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
                            }
                        }
                    }
                }
            }), 1);
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(static function() use($task): void{
                $task->getHandler()->cancel();
            }), intdiv($player->getPing(), 50) + 2);
        }elseif($packet instanceof NetworkChunkPublisherUpdatePacket){
            foreach($level->getTiles() as $tile){
                if(in_array($player, $level->getViewersForPosition($tile), true)){
                    if($tile instanceof PlaceholderInterface){
                        $placeholder = $tile->getBlock();
                        $block = $tile->getBlock(true);
                        if($block->isValid()){
                            if($placeholder instanceof Placeholder){
                                $level->sendBlocks([$player], [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * @priority MONITOR
     */
    public function onBlockBreak(BlockBreakEvent $event){
        $block = $event->getBlock();
        $level = $block->getLevel();
        $player = $event->getPlayer();
        if($block instanceof Air or ($player->isSurvival() and !$block->isBreakable($event->getItem())) or $player->isSpectator() or ($player->isOp() and $player->isCreative())){
            return;
        }
        $cancel = null;
        for($i = 0; $i <= $level->getWorldHeight(); ++$i){
            $id = $level->getBlockIdAt($block->x, $i, $block->z);
            if($id == 212){
                $affected = $cancel = true;
                break;
            }else{
                switch($id){
                    case 210:
                        if($i < $block->y){
                            $cancel = false;
                        }
                        break;
                    case 211:
                        if($i < $block->y){
                            $cancel = true;
                        }
                        break;
                    default:
                        if(!isset($cancel)){
                            $affected = false;
                            continue 2;
                        }
                        break;
                }
                $affected = true;
            }
        }
        if($affected) $event->setCancelled($cancel);
    }
    
    /**
     * @priority MONITOR
     */
    public function onBlockPlace(BlockPlaceEvent $event){
        $block = $event->getBlock();
        $level = $block->getLevel();
        $player = $event->getPlayer();
        if($player->isSpectator() or ($player->isOp() and $player->isCreative())){
            return;
        }
        $cancel = null;
        for($i = 0; $i <= $level->getWorldHeight(); ++$i){
            $id = $level->getBlockIdAt($block->x, $i, $block->z);
            if($id == 212){
                $affected = $cancel = true;
                break;
            }else{
                switch($id){
                    case 210:
                        if($i < $block->y){
                            $cancel = false;
                        }
                        break;
                    case 211:
                        if($i < $block->y){
                            $cancel = true;
                        }
                        break;
                    default:
                        if(!isset($cancel)){
                            $affected = false;
                            continue 2;
                        }
                        break;
                }
                $affected = true;
            }
        }
        if($affected) $event->setCancelled($cancel);
    }
    
    /**
     * @priority MONITOR
     */
    public function onPlayerCreation(PlayerCreationEvent $event){
        $event->setPlayerClass(ModPlayer::class);
    }
    
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onPlayerAnimate(PlayerAnimationEvent $event){
        if($event->getAnimationType() == AnimatePacket::ACTION_SWING_ARM){
            Utils::setShieldCooldown($event->getPlayer(), 5);
        }
    }
    
    /**
     * @priority MONITOR
     */
    public function onEntityDespawn(EntityDespawnEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof ItemEntity and !$entity->isAlive()){
            $item = $entity->getItem();
            if(in_array($item->getId(), [Block::SHULKER_BOX, Block::UNDYED_SHULKER_BOX])){
                $nbt = $item->getNamedTag();
                if($nbt->hasTag(Container::TAG_ITEMS, ListTag::class)){
                    foreach($nbt->getListTag(Container::TAG_ITEMS) as $itemNBT){
                        $entity->getLevel()->dropItem($entity, Item::nbtDeserialize($itemNBT));
                    }
                }
            }
        }
    }
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onPlayerLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();
	    $player->getDataPropertyManager()->setByte(Entity::DATA_COLOR, Entity::DATA_TYPE_BYTE);
	    $player->setGenericFlag(Entity::DATA_FLAG_BLOCKING, $player->isSneaking());
        $player->getInventory()->setContents(array_map(function(Item $item): Item{
            if($item->getId() >= 0) return $item;
            return ItemFactory::get($item->getId(), $item->getDamage(), $item->getCount(), $item->getNamedTag());
        }, $player->getInventory()->getContents()));
    }
    
    /**
     * @priority MONITOR
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if($player instanceof ModPlayer) return;
        $offhand = Utils::getOffhandInventory($player);
        if($player->namedtag->hasTag("Offhand", ListTag::class)){
            $offhand->setItemInOffhand(Item::nbtDeserialize($player->namedtag->getListTag("Offhand")->get(0)));
        }
    }
    
    /**
     * @priority MONITOR
     */
    public function onPlayerQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if($player instanceof ModPlayer) return;
        $player->namedtag->setTag(new ListTag("Offhand", [Utils::getOffhandInventory($player)->getItemInOffhand()->nbtSerialize()]));
    }
    
    /**
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onPlayerMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if($event->getFrom()->distance($event->getTo()) == 0) return;
        $blockBelow = $player->getLevel()->getBlock($player->subtract(0, 1));
        if($player->isOnGround()){
            $default = $player->isSprinting() ? 0.13 : 0.1;
            $armor = $player->getArmorInventory();
            $boots = $armor->getBoots();
            $attr = $player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED);
            if(($blockBelow instanceof SoulSand or $blockBelow instanceof Placeholder and $blockBelow->getBlock() instanceof SoulSoil) and $boots->hasEnchantment(Enchantment::SOUL_SPEED)){
                $multiplier = ($boots->getEnchantmentLevel(Enchantment::SOUL_SPEED)*0.105) + 1.3;
                $attr->setValue($default*$multiplier, false, true);
                if($player->isSurvival() and lcg_value() <= 0.04){
                    $boots->applyDamage(1);
                    $armor->setBoots($boots);
                }
            }else $attr->setValue($default, false, true);
        }
    }
    
    /**
     * @priority MONITOR
     */
    public function onLevelSave(LevelSaveEvent $event){
        foreach(array_filter($event->getLevel()->getEntities(), function(Entity $entity): bool{
            return $entity->canSaveWithChunk() and !$entity->isClosed() and $entity instanceof Human;
        }) as $human){
            $human->namedtag->setTag(new ListTag("Offhand", [Utils::getOffhandInventory($human)->getItemInOffhand()->nbtSerialize()]));
        }
    }
    
    /**
     * @priority MONITOR
     */
    public function onEntitySpawn(EntitySpawnEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Human and !$entity instanceof Player){
            $nbt = $entity->namedtag;
            if($nbt->hasTag("Offhand", ListTag::class)){
                Utils::getOffhandInventory($entity)->setItemInOffhand(Item::nbtDeserialize($nbt->getListTag("Offhand")->get(0)));
            }
        }
    }
    
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onPlayerToggleSneak(PlayerToggleSneakEvent $event){
        $player = $event->getPlayer();
        if(Utils::getShieldCooldown($player) <= 0){
            $player->setGenericFlag(Entity::DATA_FLAG_BLOCKING, $event->isSneaking());
        }
    }
    
    /**
     * @priority MONITOR
     */
    public function onPlayerDeath(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        if($player instanceof ModPlayer) return;
        $offhand = Utils::getOffhandInventory($player);
        if(!$event->getKeepInventory() and !empty($event->getDrops())){
            $event->setDrops(array_merge($event->getDrops(), array_filter([$offhand->getItem(0)], function(Item $item): bool{
                return !$item->hasEnchantment(Enchantment::VANISHING);
            })));
            $offhand->clearAll();
        }
    }
    
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onEntityInventoryChange(EntityInventoryChangeEvent $event){
        $entity = $event->getEntity();
        $item = $event->getNewItem();
        if($entity instanceof Human and Utils::isNetheriteArmor($item) and $item->getHolder() !== $entity){
            $event->setNewItem($item->setHolder($entity));
        }elseif($item->getVanillaName() == 'Unknown' and ItemFactory::isRegistered($item->getId())){
            $event->setNewItem(ItemFactory::get($item->getId(), $item->getDamage(), $item->getCount(), $item->getNamedTag()));
        }
    }
    
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event){
        foreach($event->getTransaction()->getActions() as $action){
            $item = $action->getTargetItem();
            if($item->getVanillaName() == 'Unknown' and ItemFactory::isRegistered($item->getId())){
                Utils::setProperty($action, 'targetItem', ItemFactory::get($item->getId(), $item->getDamage(), $item->getCount(), $item->getNamedTag()));
            }
        }
    }
    
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onEntityDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();
        if($event->getCause() == EntityDamageEvent::CAUSE_FALL){
            $blockBelow = $entity->getLevel()->getBlock($entity->subtract(0, 1));
            if($entity->hasEffect(27) or $blockBelow->getId() == Block::SLIME_BLOCK and ($entity instanceof Player ? !$entity->isSneaking() : true)){
                $event->setCancelled();
                return;
            }elseif($blockBelow->getId() == Block::HAY_BALE or $blockBelow instanceof Placeholder and $blockBelow->getBlock() instanceof HoneyBlock){
                $event->setModifier(-$event->getFinalDamage()*0.9, 11);
            }
        }elseif($event->getCause() == EntityDamageEvent::CAUSE_SUFFOCATION){
            if($entity->getGenericFlag(Entity::DATA_FLAG_SWIMMING) or $entity->getGenericFlag(Entity::DATA_FLAG_GLIDING)){
                $event->setCancelled();
                return;
            }elseif(($block = $entity->getLevel()->getBlock($entity->add(0, $entity->getEyeHeight()))) instanceof Placeholder and !$block->getBlock()->isSolid()){
                $event->setCancelled();
                return;
            }
        }
        if($entity instanceof Human and !$entity instanceof ModPlayer){
            $type = $event->getCause();
            $offhand = Utils::getOffhandInventory($entity);
            $inventory = $entity->getInventory();
    		if($type != EntityDamageEvent::CAUSE_SUICIDE and $type != EntityDamageEvent::CAUSE_VOID and $inventory->getItemInHand() instanceof Totem or $offhand->getItemInOffhand() instanceof Totem){
	    		$compensation = $entity->getHealth() - $event->getFinalDamage() - 1;
	    		if($compensation < 0){
	    		    $event->setModifier($compensation, 12);
	    		    $entity->removeAllEffects();

			        $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 40 * 20, 1));
			        $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 40 * 20));
			        $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 5 * 20, 1));
			        
			        $entity->broadcastEntityEvent(ActorEventPacket::CONSUME_TOTEM);
	    		    
			        $entity->getLevel()->broadcastLevelEvent($entity->add(0, $entity->getEyeHeight()), LevelEventPacket::EVENT_SOUND_TOTEM);
			        $hand = $offhand->getItemInOffhand();
			        if($hand instanceof Totem){
			            $hand->pop();
			            $offhand->setItemInOffhand($hand);
			        }else{
			            $hand = $inventory->getItemInHand();
			            if($hand instanceof Totem){
			                $hand->pop();
			                $inventory->setItemInHand($hand);
			            }
			        }
	    		}
	    	}
        }
    }
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        if($entity instanceof Human){
            $inventory = $entity->getInventory();
            $offhand = $entity instanceof ModPlayer ? $entity->getOffhandInventory() : Utils::getOffhandInventory($entity);
            if($entity->getGenericFlag(Entity::DATA_FLAG_BLOCKING) and ($inventory->getItemInHand() instanceof Shield or $offhand->getItemInOffhand() instanceof Shield) and $event->getCause() != EntityDamageEvent::CAUSE_MAGIC){
                $attacker = $event instanceof EntityDamageByChildEntityEvent ? $event->getChild() : $damager;
                if($entity instanceof Player ? $entity->canInteract($attacker, 8) : Utils::canInteract($entity, $attacker, 8)){
                    if($attacker instanceof Arrow) return;
                    $modifiers = $event->getModifiers();
                    unset($modifiers[1], $modifiers[4], $modifiers[5], $modifiers[6], $modifiers[8]);
                    $damage = (int)max(0, $event->getBaseDamage()+array_sum($modifiers))*2;
                    $entity->getLevel()->broadcastLevelSoundEvent($entity->add(0, $entity->getEyeHeight()), LevelSoundEventPacket::SOUND_ITEM_SHIELD_BLOCK);
                    $shield = $offhand->getItemInOffhand();
                    if($shield instanceof Shield){
                        $shield->applyDamage($damage);
                        $offhand->setItemInOffhand($shield);
                    }else{
                        $shield = $inventory->getItemInHand();
                        if($shield instanceof Shield){
                            $shield->applyDamage($damage);
                            $inventory->setItemInHand($shield);
                        }
                    }
                    if(!$event instanceof EntityDamageByChildEntityEvent){
                        if($damager instanceof Living){
                            $deltaX = $damager->x - $entity->x;
		    	    	    $deltaZ = $damager->z - $entity->z;
		    	        	$damager->knockBack($entity, 0, $deltaX, $deltaZ, 0.8);
                        }
                        if($damager instanceof Human and $damager->getInventory()->getItemInHand() instanceof Axe){
                            $entity->getLevel()->broadcastLevelSoundEvent($entity->add(0, $entity->getEyeHeight()), LevelSoundEventPacket::SOUND_BREAK);
                            Utils::setShieldCooldown($entity, 32);
                        }
                    }
                    $event->setCancelled();
                    return;
                }
            }
            $count = count(array_filter($entity->getArmorInventory()->getContents(), function(Item $item): bool{
                return Utils::isNetheriteArmor($item);
            }));
            $knockback = $event->getKnockback();
            $event->setKnockback($knockback*(1-0.2*$count));
        }// Fixed LOL
        if(!$event instanceof EntityDamageByChildEntityEvent and $damager->isSprinting()){
            $knockback = $event->getKnockback();
            $event->setKnockback(1.3*$knockback);
            if($damager instanceof Player){
                $damager->toggleSprint(false);
            }else{
                $damager->setSprinting(false);
            }
        }
    }
    
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onEntityCombustByBlock(EntityCombustByBlockEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof ItemEntity and Utils::isNetheriteType($entity->getItem())) $event->setCancelled();
    }
    
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onEntityDamageByBlock(EntityDamageByBlockEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof ItemEntity and Utils::isNetheriteType($entity->getItem()) and Utils::isCauseFire($event->getCause())) $event->setCancelled();
    }
}