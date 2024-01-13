<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\ItemBlock;
use NetherUpdate\block\BlockIds;
class Campfire extends ItemBlock{
    public function __construct(int $meta = 0){
        parent::__construct(BlockIds::CAMPFIRE, $meta, ItemIds::CAMPFIRE);
    }
    public function getMaxStackSize(): int{
        return 1;
    }
}