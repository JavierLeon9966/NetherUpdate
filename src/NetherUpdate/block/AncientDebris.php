<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Obsidian;
class AncientDebris extends Obsidian{
    use PlaceholderTrait;
    protected $id = BlockIds::ANCIENT_DEBRIS;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
    public function getName(): string{
        return "Ancient Debris";
    }
    public function getVariantBitmask(): int{
        return 0;
    }
}