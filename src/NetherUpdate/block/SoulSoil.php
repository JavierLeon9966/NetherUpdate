<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Solid;
use pocketmine\block\BlockToolType;
class SoulSoil extends Solid{
    use PlaceholderTrait;
    protected $id = BlockIds::SOUL_SOIL;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
	public function getName(): string{
	    return "Soul Soil";
	}
	public function getToolType(): int{
	    return BlockToolType::TYPE_SHOVEL;
	}
    public function getHardness(): float{
        return 0.5;
    }
    public function getVariantBitmask(): int{
        return 0;
    }
}