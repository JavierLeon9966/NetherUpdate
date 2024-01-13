<?php
declare(strict_types = 1);
namespace NetherUpdate\event\entity;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\event\entity\EntityEvent;
/**
 * @phpstan-extends EntityEvent<Living>
 */
class EntityShootCrossbowEvent extends EntityEvent implements Cancellable{
	/** @var Item */
	private $crossbow;
	/** @var Projectile */
	private $projectile;

	public function __construct(Living $shooter, Item $crossbow, Projectile $projectile){
		$this->entity = $shooter;
		$this->crossbow = $crossbow;
		$this->projectile = $projectile;
	}

	/**
	 * @return Living
	 */
	public function getEntity(){
		return $this->entity;
	}

	public function getCrossbow() : Item{
		return $this->crossbow;
	}

	/**
	 * Returns the entity considered as the projectile in this event.
	 *
	 * NOTE: This might not return a Projectile if a plugin modified the target entity.
	 */
	public function getProjectile() : Entity{
		return $this->projectile;
	}

	public function setProjectile(Entity $projectile) : void{
		if($projectile !== $this->projectile){
			if(count($this->projectile->getViewers()) === 0){
				$this->projectile->close();
			}
			$this->projectile = $projectile;
		}
	}
}