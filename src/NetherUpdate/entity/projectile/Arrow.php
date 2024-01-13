<?php
declare(strict_types = 1);
namespace NetherUpdate\entity\projectile;
use pocketmine\entity\projectile\Arrow as PMArrow;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\particle\MobSpellParticle;
use pocketmine\level\Level;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\math\VoxelRayTrace;
use pocketmine\math\RayTraceResult;
use pocketmine\timings\Timings;
use pocketmine\entity\{Entity, EffectInstance, Living, Human};
use pocketmine\item\{ItemFactory, Item};
use pocketmine\utils\Color;
use pocketmine\Player;
use NetherUpdate\item\Shield;
use NetherUpdate\item\Potion;
use NetherUpdate\{Utils, Player as ModPlayer};
class Arrow extends PMArrow{
	/** @var int */
	protected $potionId;
	/** @var Color */
	protected $color;
	protected $damage = 1.2;
	public function initEntity(): void{
		$this->potionId = $this->namedtag->getShort("Potion", 0);
		if($this->potionId >= 1 && $this->potionId <= 42){
			$this->color = Color::mix(...array_map(function(EffectInstance $effect): Color{
			    return $effect->getColor();
			}, Potion::getPotionEffectsById($this->potionId)));
		}

		parent::initEntity();
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void{
		$damage = $this->getResultDamage();

		if($damage >= 0){
			if($this->getOwningEntity() === null){
				$ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
			}else{
				$ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
			}

			$entityHit->attack($ev);

			if($this->isOnFire()){
				$ev = new EntityCombustByEntityEvent($this, $entityHit, 5);
				$ev->call();
				if(!$ev->isCancelled()){
					$entityHit->setOnFire($ev->getDuration());
				}
			}
		}
		$piercing = $this->namedtag->getInt("piercing", 0);
		if($piercing == 0) $this->flagForDespawn();
		elseif($piercing > 0) $this->namedtag->setInt("piercing", $piercing - 1);
		if($this->punchKnockback > 0){
			$horizontalSpeed = sqrt($this->motion->x ** 2 + $this->motion->z ** 2);
			if($horizontalSpeed > 0){
				$multiplier = $this->punchKnockback * 0.6 / $horizontalSpeed;
				$entityHit->setMotion($entityHit->getMotion()->add($this->motion->x * $multiplier, 0.1, $this->motion->z * $multiplier));
			}
		}

		if($this->potionId >= 1 && $this->potionId <= 42 && $entityHit instanceof Living){
			foreach(Potion::getPotionEffectsById($this->potionId) as $effect){
				$entityHit->addEffect($effect);
			}
		}
	}

	public function onUpdate(int $currentTick): bool{
		$hasUpdate = parent::onUpdate($currentTick);

		if($this->potionId >= 1 && $this->potionId <= 42){
			if(!$this->isOnGround() or ($this->isOnGround() and ($currentTick % 4) == 0)){
				if($this->getLevel() instanceof Level && $this->color instanceof Color){
					$this->getLevel()->addParticle(new MobSpellParticle($this->asVector3(), $this->color->getR(), $this->color->getG(), $this->color->getB(), $this->color->getA()));
				}
			}
			$hasUpdate = true;
		}

		return $hasUpdate;
	}
	public function onCollideWithPlayer(Player $player) : void{
		if($this->blockHit === null) return;
		$item = ItemFactory::get(Item::ARROW, $this->potionId == 0 ? 0 : $this->potionId + 1, 1);
		$offhandInventory = $player instanceof ModPlayer ? $player->getOffhandInventory() : Utils::getOffhandInventory($player);
		$playerInventory = $player->getInventory();
		if($offhandInventory->first($item) > -1) $inventory = $offhandInventory;
		else $inventory = $playerInventory;
		if($player->isSurvival() and !$inventory->canAddItem($item)){
			return;
		}

		$ev = new InventoryPickupArrowEvent($inventory, $this);
		if($this->pickupMode === self::PICKUP_NONE or ($this->pickupMode === self::PICKUP_CREATIVE and !$player->isCreative())){
			$ev->setCancelled();
		}

		$ev->call();
		if($ev->isCancelled()){
			return;
		}

		$pk = new TakeItemActorPacket();
		$pk->eid = $player->getId();
		$pk->target = $this->getId();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		if($player->isSurvival()) $inventory->addItem(clone $item);
		$this->flagForDespawn();
	}
    public function move(float $dx, float $dy, float $dz) : void{
		$this->blocksAround = null;

		Timings::$entityMoveTimer->startTiming();

		$start = $this->asVector3();
		$end = $start->add($this->motion);

		$blockHit = null;
		$entityHit = null;
		$hitResult = null;

		foreach(VoxelRayTrace::betweenPoints($start, $end) as $vector3){
			$block = $this->level->getBlockAt($vector3->x, $vector3->y, $vector3->z);

			$blockHitResult = $this->calculateInterceptWithBlock($block, $start, $end);
			if($blockHitResult !== null){
				$end = $blockHitResult->hitVector;
				$blockHit = $block;
				$hitResult = $blockHitResult;
				break;
			}
		}

		$entityDistance = PHP_INT_MAX;

		$newDiff = $end->subtract($start);
		$pierced = false;
		foreach($this->level->getCollidingEntities($this->boundingBox->addCoord($newDiff->x, $newDiff->y, $newDiff->z)->expand(1, 1, 1), $this) as $entity){
			if($entity->getId() === $this->getOwningEntityId() and $this->ticksLived < 5){
				continue;
			}
			
			$entityBB = $entity->boundingBox->expandedCopy(0.3, 0.3, 0.3);
			$entityHitResult = $entityBB->calculateIntercept($start, $end);

			if($entityHitResult === null){
				continue;
			}
			if($entity instanceof Human){
			    $offhand = $entity instanceof ModPlayer ? $entity->getOffhandInventory() : Utils::getOffhandInventory($entity);
			    if($entity->getGenericFlag(Entity::DATA_FLAG_BLOCKING) and ($offhand->getItemInOffhand() instanceof Shield or $entity->getInventory()->getItemInHand() instanceof Shield) and ($entity instanceof Player ? $entity->canInteract($this, 8) : Utils::canInteract($entity, $this, 8))){
			        if($this->namedtag->getInt("piercing", 0) < 1){
			            $this->setMotion($entity->getDirectionVector()->multiply(0.8));
			            $damage = (int)$this->getResultDamage()*2;
			            $entity->getLevel()->broadcastLevelSoundEvent($entity->add(0, $entity->getEyeHeight()), LevelSoundEventPacket::SOUND_ITEM_SHIELD_BLOCK);
			            $shield = $offhand->getItemInOffhand();
			            if($shield instanceof Shield){
			                $shield->applyDamage($damage);
			                $offhand->setItemInOffhand($shield);
			            }else{
			                $shield = $entity->getInventory()->getItemInHand();
			                if($shield instanceof Shield){
			                    $shield->applyDamage($damage);
			                    $entity->getInventory()->setItemInHand($shield);
			                }
			            }
			            continue;
			        }
			    }
			}
			if($this->namedtag->getInt("piercing", 0) >= 1) $pierced = true;
			
			$distance = $this->distanceSquared($entityHitResult->hitVector);

			if($distance < $entityDistance){
				$entityDistance = $distance;
				$entityHit = $entity;
				$hitResult = $entityHitResult;
				if(Utils::getProperty($entity, "attackTime") > 0 or !$pierced) $end = $entityHitResult->hitVector;
				break;
			}
		}

		$this->x = $end->x;
		$this->y = $end->y;
		$this->z = $end->z;
		$this->recalculateBoundingBox();

		if($hitResult !== null){
			/** @var ProjectileHitEvent|null $ev */
			$ev = null;
			if($entityHit !== null){
				$ev = new ProjectileHitEntityEvent($this, $hitResult, $entityHit);
			}elseif($blockHit !== null){
				$ev = new ProjectileHitBlockEvent($this, $hitResult, $blockHit);
			}else{
				assert(false, "unknown hit type");
			}

			if($ev !== null){
				$ev->call();
				$this->onHit($ev);

				if($ev instanceof ProjectileHitEntityEvent){
					$this->onHitEntity($ev->getEntityHit(), $ev->getRayTraceResult());
				}elseif($ev instanceof ProjectileHitBlockEvent){
					$this->onHitBlock($ev->getBlockHit(), $ev->getRayTraceResult());
				}
			}
			if(!$pierced){
	    		$this->isCollided = $this->onGround = true;
	    		$this->motion->x = $this->motion->y = $this->motion->z = 0;
			}else{
			    $this->isCollided = $this->onGround = false;
		    	$this->blockHit = $this->blockHitId = $this->blockHitData = null;
			    $f = sqrt(($this->motion->x ** 2) + ($this->motion->z ** 2));
			    $this->yaw = (atan2($this->motion->x, $this->motion->z) * 180 / M_PI);
	    		$this->pitch = (atan2($this->motion->y, $f) * 180 / M_PI);
			}
		}else{

			//recompute angles...
			$f = sqrt(($this->motion->x ** 2) + ($this->motion->z ** 2));
			$this->yaw = (atan2($this->motion->x, $this->motion->z) * 180 / M_PI);
			$this->pitch = (atan2($this->motion->y, $f) * 180 / M_PI);
		}

		$this->checkChunks();
		$this->checkBlockCollision();

		Timings::$entityMoveTimer->stopTiming();
	}
}