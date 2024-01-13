<?php
declare(strict_types = 1);
namespace NetherUpdate\inventory;
use pocketmine\inventory\BaseInventory;
use pocketmine\network\mcpe\protocol\{MobEquipmentPacket, InventoryContentPacket, InventorySlotPacket};
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\item\Item;
use pocketmine\entity\Human;
use pocketmine\Player;
class PlayerOffhandInventory extends BaseInventory{
    /** @var Human */
	protected $holder;
	public function __construct(Human $holder){
		$this->holder = $holder;
		parent::__construct();
	}
	public function getName() : string{
		return "Offhand";
	}
	public function getDefaultSize() : int{
		return 1;
	}
	public function setSize(int $size){
		throw new \BadMethodCallException("Offhand can only carry one item at a time");
	}
	public function setItemInOffhand(Item $item): bool{
	    return $this->setItem(0, $item);
	}
	public function getItemInOffhand(): Item{
	    return $this->getItem(0);
	}
	public function onSlotChange(int $index, Item $before, bool $send): void{
	    $holder = $this->getHolder();
	    if($holder instanceof Player && !$holder->spawned){
	        return;
	    }
	    $this->sendContents($this->getViewers());
	    $this->sendContents($holder->getViewers());
	}
	public function sendContents($target): void{
	    if($target instanceof Player){
	        $target = [$target];
	    }
	    $item = $this->getItem(0);
	    $pk = $this->createMobEquipmentPacket($item);
	    foreach($target as $player){
	        if($player === $this->holder){
	            $pk = new InventoryContentPacket();
		        $pk->items = array_map([ItemStackWrapper::class, 'legacy'], [$item]);
		        $pk->windowId = ContainerIds::OFFHAND;
		        $player->dataPacket($pk);
	        }else $player->batchDataPacket($pk);
	    }
	}
	public function sendSlot(int $index, $target) : void{
		if($target instanceof Player){
			$target = [$target];
		}
		$item = $this->getItem(0);
		$pk = $this->createMobEquipmentPacket($item);
		foreach($target as $player){
		    if($player === $this->holder){
		        $pk = new InventorySlotPacket;
		        $pk->inventorySlot = 0;
		        $pk->item = ItemStackWrapper::legacy($item);
		        $pk->windowId = ContainerIds::OFFHAND;
		        $player->dataPacket($pk);
	        }else $player->batchDataPacket($pk);
		}
	}
	private function createMobEquipmentPacket(Item $item): MobEquipmentPacket{
		$pk = new MobEquipmentPacket;
		$pk->windowId = ContainerIds::OFFHAND;
		$pk->item = $this->getItem(0);
		$pk->inventorySlot = $pk->hotbarSlot = 0;
		$pk->entityRuntimeId = $this->getHolder()->getId();
	    $pk->encode();
	    return $pk;
	}
	public function getHolder(): Human{
		return $this->holder;
	}
}