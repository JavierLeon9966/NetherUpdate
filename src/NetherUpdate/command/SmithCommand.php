<?php
declare(strict_types = 1);
namespace NetherUpdate\command;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\Armor;
use pocketmine\Player;
use NetherUpdate\item\NetheriteIngot;
use NetherUpdate\item\ItemIds;
use NetherUpdate\libs\muqsit\invmenu\InvMenu;
use NetherUpdate\libs\muqsit\invmenu\inventories\ChestInventory;
use NetherUpdate\Utils;
class SmithCommand extends Command{
    public function __construct(){
        parent::__construct("smith", "Upgrade diamond gear into netherite gear.");
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage("Only players can use this command!");
            return;
        }
        $barriers = [];
        for($i = 0; $i < 27; $i++){
            if(in_array($i, [10, 13, 16])) continue;
            $barriers[$i] = new Item(-161, 0, "Barrier");
        }
        $menu = new InvMenu(ChestInventory::class, $barriers);
        $menu->setName("Upgrade Gear");
        $menu->setInventoryCloseListener(function(Player $player, ChestInventory $inventory): void{
            foreach([10, 13] as $i){
                $item = $inventory->getItem($i);
                $drops = $player->getInventory()->addItem($item);
                foreach($drops as $drop) $player->dropItem($drop);
            }
            $inventory->clearAll();
        });
        $menu->setListener(function(Player $player, Item $itemClicked, Item $itemReplaced, SlotChangeAction $action): bool{
            $inventory = $action->getInventory();
            switch($action->getSlot()){
                case 10:
                    if($this->isDiamondGear($itemReplaced)){
                        if($inventory->getItem(13) instanceof NetheriteIngot){
                            $name = $itemReplaced->getVanillaName();
                            $id = constant(ItemIds::class.'::'.strtoupper(str_replace("Diamond ", "Netherite_", $name)));
                            $inventory->setItem(16, Item::get($id, $itemReplaced->getDamage(), 1, $itemReplaced->getNamedTag()));
                            break;
                        }
                    }
                    $inventory->clear(16);
                    break;
                case 13:
                    if($itemReplaced instanceof NetheriteIngot){
                        if($this->isDiamondGear($gear = $inventory->getItem(10))){
                            $name = $gear->getVanillaName();
                            $id = constant(ItemIds::class.'::'.strtoupper(str_replace("Diamond ", "Netherite_", $name)));
                            $inventory->setItem(16, Item::get($id, $gear->getDamage(), 1, $gear->getNamedTag()));
                            break;
                        }
                    }
                    $inventory->clear(16);
                    break;
                case 16:
                    $return = true;
                    $removeItems = true;
                    if(!$itemReplaced->isNull()){
                        if($itemClicked->isNull() and $this->isDiamondGear($gear = $inventory->getItem(10)) and $inventory->getItem(13) instanceof NetheriteIngot){
                            $name = $gear->getVanillaName();
                            $id = constant(ItemIds::class.'::'.strtoupper(str_replace("Diamond ", "Netherite_", $name)));
                            $inventory->setItem(16, Item::get($id, $gear->getDamage(), 1, $gear->getNamedTag()));
                            return false;
                        }elseif(!$itemClicked->isNull()){
                            if($player->getInventory()->canAddItem($itemClicked)){
                                $inventory->clear(16);
                                $player->getInventory()->addItem($itemClicked);
                            }else $removeItems = false;
                            $return = false;
                        }else return false;
                    }
                    if(!$itemClicked->isNull() and $removeItems){
                        foreach([10, 13] as $i){
                            $item = $inventory->getItem($i);
                            if(!$item->isNull()){
                                $item->pop();
                                $inventory->setItem($i, $item);
                            }
                        }
                    }
                    if($this->isDiamondGear($gear = $inventory->getItem(10)) and $inventory->getItem(13) instanceof NetheriteIngot){
                        $name = $gear->getVanillaName();
                        $id = constant(ItemIds::class.'::'.strtoupper(str_replace("Diamond ", "Netherite_", $name)));
                        if($return){
                            Utils::setProperty($action, "targetItem", Item::get($id, $gear->getDamage(), 1, $gear->getNamedTag()));
                        }else{
                            $inventory->setItem(16, Item::get($id, $gear->getDamage(), 1, $gear->getNamedTag()));
                        }
                    }
                    $player->getLevel()->broadcastLevelSoundEvent($player->add(0, $player->getEyeHeight()), 316);
                    return $return;
                default:
                    return false;
            }
            return true;
        });
        $menu->send($sender);
    }
    private function isDiamondGear(Item $item): bool{
        return in_array($item->getId(), [276, 277, 278, 279, 293, 310, 311, 312, 313]);
    }
}