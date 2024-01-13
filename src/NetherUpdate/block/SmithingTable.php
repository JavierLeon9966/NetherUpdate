<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Solid;
use pocketmine\block\BlockToolType;
use pocketmine\item\Item;
use pocketmine\Player;
class SmithingTable extends Solid{
    use PlaceholderTrait;
    protected $id = BlockIds::SMITHING_TABLE;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
    public function getName(): string{
        return "Smithing Table";
    }
    public function getHardness(): float{
        return 2.5;
    }
    public function getToolType(): int{
        return BlockToolType::TYPE_AXE;
    }
    public function getVariantBitmask(): int{
        return 0;
    }
    public function getFuelTime(): int{
        return 300;
    }
    public function onActivate(Item $item, Player $player = null): bool{
        if($player !== null){
            return $player->chat("/smith");
        }
        return false;
    }
}