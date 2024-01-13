<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Transparent;
class HoneycombBlock extends Transparent{
    use PlaceholderTrait;
    protected $id = BlockIds::HONEYCOMB_BLOCK;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
	public function getName(): string{
	    return "Honeycomb Block";
	}
    public function getHardness(): float{
        return 0.6;
    }
    public function getVariantBitmask(): int{
        return 0;
    }
}