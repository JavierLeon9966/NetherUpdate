<?php
declare(strict_types = 1);
namespace NetherUpdate\command;
use DaPigGuy\PiggyCustomEnchants\enchants\CustomEnchant;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\tile\Chest;
use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\Player;
use NetherUpdate\Main;
use ReflectionClass;
class RegisterCommand extends Command{
    public function __construct(){
        parent::__construct("register", "Register a crate in a file.");
        $this->setPermission("netherupdate.command.register");
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage("Only players can execute this command!");
            return;
        }elseif(!$this->testPermission($sender)) return;
        $config = [
            'crates' => [
                'Example' => [
                    'drops' => []
                ]
            ]
        ];
        if(($chest = $sender->getLevel()->getTile($sender->getTargetBlock(10))) instanceof Chest){
            foreach(array_values($chest->getInventory()->getContents()) as $item){
                $config['crates']['Example']['drops'][] = [
                    'id' => $item->getId(),
                    'meta' => $item->getDamage(),
                    'amount' => $item->getCount(),
                    'name' => $item->getCustomName(),
                    'enchantments' => array_map(function(EnchantmentInstance $enchantment): array{
                        $enchant = $enchantment->getType();
                        if($enchant instanceof CustomEnchant) $name = str_replace(" ", "", $enchant->name);
                        else{
                            foreach((new ReflectionClass(Enchantment::class))->getConstants() as $name => $id){
                                if($enchant->getId() != $id) continue;
                                break;
                            }
                        }
                        return [
                            'name' => strtolower($name),
                            'level' => $enchantment->getLevel()
                        ]; 
                    }, $item->getEnchantments())
                ];
            }
            $file = new Config(Main::getInstance()->getDataFolder()."crates.yml", Config::YAML);
            $file->setAll($config);
            $file->save();
            $sender->sendMessage("§aSuccessfully saved!");
        }else $sender->sendMessage("§cTarget block isn't a chest!");
    }
}