<?php
declare (strict_types = 1);
namespace NetherUpdate\block;
use pocketmine\block\{BlockFactory as PMFactory, Reserved6, UnknownBlock};
use pocketmine\Server;
class BlockFactory extends PMFactory{
    public static function init(): void{
        self::getBlockStatesArray()->setSize(16384);
        self::$solid->setSize(1024);
        self::$transparent->setSize(1024);
        self::$hardness->setSize(1024);
        self::$light->setSize(1024);
        self::$lightFilter->setSize(1024);
        self::$diffusesSkyLight->setSize(1024);
        self::$blastResistance->setSize(1024);
        //foreach(Server::getInstance()->getLevels() as $level) $level->getRandomTickedBlocks()->setSize(1024);
        self::registerBlock(new Reserved6(242, 0, "Camera Block"));
        self::registerBlock(new Placeholder, true);
        self::registerBlock(new Lodestone);
        self::registerBlock(new Barrier);
        self::registerBlock(new Allow);
        self::registerBlock(new Deny);
        self::registerBlock(new Border);
        self::registerBlock(new SmithingTable);
        self::registerBlock(new NetheriteBlock);
        self::registerBlock(new AncientDebris);
        self::registerBlock(new SmoothStone);
        self::registerBlock(new FletchingTable);
        self::registerBlock(new SoulSoil);
        self::registerBlock(new HoneyBlock);
        self::registerBlock(new HoneycombBlock);
        
        for($id = 0, $size = self::getBlockStatesArray()->getSize() >> 4; $id < $size; ++$id){
			if(!self::getBlockStatesArray()[$id << 4]){
				self::registerBlock(new UnknownBlock($id));
			}
		}
    }
}