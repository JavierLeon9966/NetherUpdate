<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Item;
class NetheriteIngot extends Item{
    public function __construct(){
        parent::__construct(ItemIds::NETHERITE_INGOT, 0, "Netherite Ingot");
    }
}