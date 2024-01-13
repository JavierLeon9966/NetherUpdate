<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\ItemBlock;
use NetherUpdate\block\BlockIds;
class SoulCampfire extends ItemBlock{
    public function __construct(int $meta = 0){
        parent::__construct(BlockIds::SOUL_CAMPFIRE, $meta, ItemIds::SOUL_CAMPFIRE);
    }
    public function getMaxStackSize(): int{
        return 1;
    }
}