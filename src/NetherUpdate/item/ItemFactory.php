<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\{ItemFactory as PMFactory, Item, Tool, Armor, Durable, ItemBlock};
use pocketmine\nbt\tag\CompoundTag;
use NetherUpdate\block\BlockIds;
use NetherUpdate\block\BlockFactory;
use NetherUpdate\Utils;
use const pocketmine\RESOURCE_PATH;
class ItemFactory extends PMFactory{
    public static function init(): void{
        //Removes limitations
        Utils::setProperty(parent::class, 'list', Utils::getProperty(parent::class, 'list')->toArray());
        
        self::registerItem(new NetheriteHelmet);
        self::registerItem(new TurtleHelmet);
        self::registerItem(new NetheriteChestplate);
        self::registerItem(new NetheriteLeggings);
        self::registerItem(new NetheriteBoots);
        self::registerItem(new Crossbow);
        self::registerItem(new Bow, true);
        self::registerItem(new Sword(ItemIds::NETHERITE_SWORD, 0, "Netherite Sword", 6));
        self::registerItem(new Shovel(ItemIds::NETHERITE_SHOVEL, 0, "Netherite Shovel", 6));
        self::registerItem(new Pickaxe(ItemIds::NETHERITE_PICKAXE, 0, "Netherite Pickaxe", 6));
        self::registerItem(new Axe(ItemIds::NETHERITE_AXE, 0, "Netherite Axe", 6));
        self::registerItem(new Hoe(ItemIds::NETHERITE_HOE, 0, "Netherite Hoe", 6));
        self::registerItem(new NetheriteIngot);
        self::registerItem(new Item(ItemIds::NETHERITE_SCRAP, 0, "Netherite Scrap"));
        self::registerItem(new Campfire);
        self::registerItem(new Shield);
        self::registerItem(new WarpedFungusOnAStick);
        self::registerItem(new SoulCampfire);
        self::registerItem(new Sign(BlockIds::CRIMSON_STANDING_SIGN, 0, ItemIds::CRIMSON_SIGN));
        self::registerItem(new Sign(BlockIds::WARPED_STANDING_SIGN, 0, ItemIds::WARPED_SIGN));
        self::registerItem(new Sign(BlockIds::ACACIA_STANDING_SIGN, 0, Item::ACACIA_SIGN));
        self::registerItem(new Sign(BlockIds::DARKOAK_STANDING_SIGN, 0, Item::DARKOAK_SIGN));
        self::registerItem(new Sign(BlockIds::JUNGLE_STANDING_SIGN, 0, Item::JUNGLE_SIGN));
        self::registerItem(new Sign(BlockIds::BIRCH_STANDING_SIGN, 0, Item::BIRCH_SIGN));
        self::registerItem(new Sign(BlockIds::SPRUCE_STANDING_SIGN, 0, Item::SPRUCE_SIGN));
        self::registerItem(new ItemBlock(BlockIds::CRIMSON_DOOR, 0, ItemIds::CRIMSON_DOOR));
        self::registerItem(new ItemBlock(BlockIds::WARPED_DOOR, 0, ItemIds::WARPED_DOOR));
        self::registerItem(new ItemBlock(BlockIds::CHAIN, 0, ItemIds::CHAIN));
        self::registerItem(new ItemBlock(BlockIds::NETHER_SPROUTS, 0, ItemIds::NETHER_SPROUTS));
        self::registerItem(new LodestoneCompass);
        self::registerItem(new Item(ItemIds::HONEYCOMB, 0, "Honeycomb"));
        self::registerItem(new HoneyBottle);
        self::registerItem(new SuspiciousStew);
        self::registerItem(new Potion, true);
        self::registerItem(new Totem, true);
        self::registerItem(new Elytra);
        $i = 0;
        $slot = -1;
        foreach(self::fromString("Diamond Helmet,Chain Helmet,Gold Helmet,Iron Helmet,Leather Cap,Diamond Chestplate, Chain Chestplate,Gold Chestplate,Iron Chestplate,Leather Tunic,Diamond Leggings,Chain Leggings,Gold Leggings,Iron Leggings,Leather Pants,Diamond Boots,Chain Boots,Gold Boots,Iron Boots,Leather Boots", true) as $armor){
            if(is_int($i++/5)) $slot++;
            $id = $armor->getId();
            $name = $armor->getName();
            $defensePoints = $armor->getDefensePoints();
            $maxDurability = $armor->getMaxDurability();
            self::registerItem(new class($id, $name, $slot, $defensePoints, $maxDurability) extends Armor{
                use AEquipTrait;
                protected $defensePoints, $maxDurability;
                public function __construct(int $id, string $name, int $slot, int $defensePoints, int $maxDurability){
                    parent::__construct($id, 0, $name);
                    $this->slot = $slot;
                    $this->defensePoints = $defensePoints;
                    $this->maxDurability = $maxDurability;
                }
                public function getDefensePoints(): int{
                    return $this->defensePoints;
                }
                public function getMaxDurability(): int{
                    return $this->maxDurability;
                }
            }, true);
        }
        foreach(self::fromString("Diamond Hoe,Stone Hoe,Gold Hoe,Iron Hoe,Wooden Hoe", true) as $item){
            $id = $item->getId();
            $name = $item->getName();
            $tier = $item->getTier();
            self::registerItem(new Hoe($id, 0, $name, $tier), true);
        }
    }
    public static function get(int $id, int $meta = 0, int $count = 1, $tags = null) : Item{
		if(!is_string($tags) and !$tags instanceof CompoundTag and $tags !== null){
			throw new \TypeError("`tags` argument must be a string or CompoundTag instance, " . (is_object($tags) ? "instance of " . get_class($tags) : gettype($tags)) . " given");
		}
		$listed = Utils::getProperty(parent::class, 'list')[$id] ?? null;
		if($listed !== null) $item = clone $listed;
		elseif($id < 256) $item = new ItemBlock($id < 0 ? 255 - $id : $id, $meta, $id);
		else $item = new Item($id, $meta);

		$item->setDamage($meta);
		$item->setCount($count);
		$item->setCompoundTag($tags);
		return $item;
	}
	public static function isRegistered(int $id): bool{
	    if($id < 256){
	        return BlockFactory::isRegistered($id < 0 ? 255 - $id : $id);
	    }
	    return parent::isRegistered($id);
	}
    public static function addCreativeItem(Item $item): void{
        if(Item::isCreativeItem($item)) return;
        $creativeItems = json_decode(file_get_contents(RESOURCE_PATH ."vanilla" . DIRECTORY_SEPARATOR . "creativeitems.json"), true);
        foreach($creativeItems as $i => $d){
            if(Item::jsonDeserialize($d)->equals($item, !$item instanceof Durable)){
                $items = Item::getCreativeItems();
                $items[$i] = $item;
                Utils::setProperty(Item::class, 'creative', $items);
                break;
            }
        }
    }
    public static function initCreativeItems(): void{
        Item::clearCreativeItems();

		$creativeItems = json_decode(file_get_contents(RESOURCE_PATH . "vanilla". DIRECTORY_SEPARATOR . "creativeitems.json"), true);

		foreach($creativeItems as $data){
			$item = self::jsonDeserialize($data);
			if($item->getVanillaName() == 'Unknown') continue;
			Item::addCreativeItem($item);
		}
    }
    final private static function jsonDeserialize(array $data) : Item{
		$nbt = "";

		//Backwards compatibility
		if(isset($data["nbt"])){
			$nbt = $data["nbt"];
		}elseif(isset($data["nbt_hex"])){
			$nbt = hex2bin($data["nbt_hex"]);
		}elseif(isset($data["nbt_b64"])){
			$nbt = base64_decode($data["nbt_b64"], true);
		}
		return self::get(
			(int) $data["id"],
			(int) ($data["damage"] ?? 0),
			(int) ($data["count"] ?? 1),
			(string) $nbt
		);
	}
}