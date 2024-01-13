<?php
declare(strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\{Block, Solid, BlockToolType};
use pocketmine\item\{Item, Compass, TieredTool};
use pocketmine\math\Vector3;
use pocketmine\Player;
use NetherUpdate\item\LodestoneCompass;
use NetherUpdate\tile\Lodestone as TileLodestone;
use pocketmine\tile\Tile;
class Lodestone extends Solid{
    use PlaceholderTrait;
    protected $id = BlockIds::LODESTONE;
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }
    public function getName(): string{
        return "Lodestone";
    }
    public function getHardness() : float{
		return 3.5;
	}
	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}
	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_WOODEN;
	}
	public function getVariantBitmask(): int{
	    return 0;
	}
    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $nbt = TileLodestone::createNBT($this);
        $nbt->setInt("trackingHandle", TileLodestone::$lodestones++);
		$this->getLevelNonNull()->setBlock($this, new Placeholder($this, Tile::createTile("Lodestone", $this->getLevel(), $nbt)), true);
		return true;
	}
	public function onActivate(Item $item, Player $player = null) : bool{
	    if($player instanceof Player){
	        $tile = $this->getLevel()->getTile($this);
	        if(!$tile instanceof TileLodestone) return false;
	        if($item instanceof Compass){
                $item->pop();
                $lodestone = new LodestoneCompass;
                $lodestone->setNamedTag($item->getNamedTag());
                $lodestone->setTrackingHandle($tile->getTrackingHandle());
                if($player->isSurvival()){
                    if($item->isNull()){
                        $player->getInventory()->setItemInHand($lodestone);
                        $this->getLevel()->broadcastLevelSoundEvent($this->add(0.5, 0.5, 0.5), 315);
                        return true;
                    }else $player->getInventory()->setItemInHand($item);
                    $drops = $player->getInventory()->addItem($lodestone);
                    foreach($drops as $drop) $player->dropItem($drop);
                }elseif($player->isCreative()){
                    $drops = $player->getInventory()->addItem($lodestone);
                    foreach($drops as $drop) $player->dropItem($drop);
                }
                $this->getLevel()->broadcastLevelSoundEvent($this->add(0.5, 0.5, 0.5), 315);
                return true;
	        }elseif($item instanceof LodestoneCompass){
                $item->setTrackingHandle($tile->getTrackingHandle());
                $player->getInventory()->setItemInHand($item);
                $this->getLevel()->broadcastLevelSoundEvent($this->add(0.5, 0.5, 0.5), 315);
                return true;
	        }
	    }
		return false;
	}
}