<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Solid;
use pocketmine\block\BlockToolType;
use pocketmine\item\TieredTool;
class SmoothStone extends Solid{
    use PlaceholderTrait;
    protected $id = BlockIds::SMOOTH_STONE;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
	public function getVariantBitmask(): int{
	    return 0;
	}
	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_WOODEN;
	}

	public function getName() : string{
		return "Smooth Stone";
	}
	public function getHardness(): float{
	    return 2;
	}
}