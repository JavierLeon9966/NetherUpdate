<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Transparent;
class HoneyBlock extends Transparent{
    use PlaceholderTrait;
    protected $id = BlockIds::HONEY_BLOCK;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
	public function getName(): string{
	    return "Honey Block";
	}
    public function getHardness(): float{
        return 0.0;
    }
    public function getVariantBitmask(): int{
        return 0;
    }
}