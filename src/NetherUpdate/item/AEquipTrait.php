<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\math\Vector3;
use pocketmine\Player;
trait AEquipTrait{
    protected $slot = 0;
    public function onClickAir(Player $player, Vector3 $directionVector) : bool{
        $player->getInventory()->setItemInHand($player->getArmorInventory()->getItem($this->slot));
        $player->getArmorInventory()->setItem($this->slot, $this);
		return false;
    }
    public function getType(){
        $type = ["Helmet", "Chestplate", "Leggings", "Boots"];
        return $type[$this->slot];
    }
    public function getSlot(){
        return $this->slot;
    }
}