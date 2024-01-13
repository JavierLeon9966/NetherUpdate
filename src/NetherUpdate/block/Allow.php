<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\Bedrock;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
class Allow extends Bedrock{
    protected $id = 210;
    public function getName(): string{
        return "Allow";
    }
    public function getVariantBitmask(): int{
        return 0;
    }
    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool {
        if($player !== null){
            if(!$player->isCreative() || !$player->isOp()){
                return false;
            }
        }
        return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }
}