<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Bedrock;
class Barrier extends Bedrock{
    use PlaceholderTrait;
    protected $id = BlockIds::BARRIER;
    protected $itemId = 255 - BlockIds::BARRIER;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
    public function getName(): string{
        return "Barrier";
    }
    public function getLightFilter(): int{
        return 0;
    }
    public function getVariantBitmask(): int{
        return 0;
    }
}