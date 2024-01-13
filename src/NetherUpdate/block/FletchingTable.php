<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Solid;
use pocketmine\block\BlockToolType;
class FletchingTable extends Solid{
    use PlaceholderTrait;
    protected $id = BlockIds::FLETCHING_TABLE;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
	public function getName(): string{
	    return "Fletching Table";
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
}