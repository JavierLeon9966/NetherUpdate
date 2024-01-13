<?php
declare(strict_types = 1);
namespace NetherUpdate\tile;
use pocketmine\tile\Spawnable;
use pocketmine\level\Level;
use pocketmine\nbt\tag\{CompoundTag, IntTag};
use pocketmine\utils\Config;
use NetherUpdate\Main;
class Lodestone extends Spawnable implements PlaceholderInterface{
    use PlaceholderTrait;
	protected $trackingHandle = 1;
	public static $lodestones = 1;
	private static $created = false;
	public function __construct(Level $level, CompoundTag $nbt){
	    if(!self::$created){
	        self::$created = true;
	        self::$lodestones = max(1, (int)(new Config(Main::getInstance()->getDataFolder().'lodestones.yml', Config::YAML))->get('trackingIds'));
	    }
	    if(!$nbt->hasTag("trackingHandle", IntTag::class)){
	        $nbt->setInt("trackingHandle", self::$lodestones++);
	    }
	    parent::__construct($level, $nbt);
	}
	public function getDefaultName(): string{
	    return "Lodestone";
	}
	public function getTrackingHandle(): int{
	    return $this->trackingHandle;
	}
	public function saveNBT(): CompoundTag{
	    $lodestones = new Config(Main::getInstance()->getDataFolder().'lodestones.yml', Config::YAML);
	    $lodestones->set('trackingIds', self::$lodestones);
	    $lodestones->save();
	    return parent::saveNBT();
	}
	protected function readSaveData(CompoundTag $nbt): void{
	    $this->trackingHandle = $nbt->getInt("trackingHandle", 1);
	    $this->loadBlock($nbt);
	}
	protected function writeSaveData(CompoundTag $nbt): void{
	    $nbt->setInt("trackingHandle", $this->trackingHandle);
	    $this->saveBlock($nbt);
	}
	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
	    $this->writeSaveData($nbt);
	    $nbt->removeTag("Block");
	}
}