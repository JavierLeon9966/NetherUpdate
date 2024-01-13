<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Obsidian;
class NetheriteBlock extends Obsidian{
    use PlaceholderTrait;
    protected $id = BlockIds::NETHERITE_BLOCK;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
    public function getName(): string{
        return "Netherite Block";
    }
    public function getVariantBitmask(): int{
        return 0;
    }
}