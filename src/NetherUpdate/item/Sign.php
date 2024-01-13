<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\ItemBlock;
class Sign extends ItemBlock{
    public function getMaxStackSize(): int{
        return 16;
    }
}