<?php
namespace NetherUpdate;
use pocketmine\plugin\PluginBase;
use pocketmine\item\Item;
use pocketmine\tile\Tile;
use pocketmine\entity\{Entity, Effect, EffectInstance};
use pocketmine\event\Listener;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Color;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use NetherUpdate\Player as ModPlayer;
use NetherUpdate\command\SmithCommand;
use NetherUpdate\command\RegisterCommand;
use NetherUpdate\tile\Lodestone;
use NetherUpdate\tile\Placeholder;
use NetherUpdate\item\{LodestoneCompass, TurtleHelmet};
use NetherUpdate\item\enchantment\Enchantment;
use NetherUpdate\entity\projectile\{Arrow, SplashPotion};
use NetherUpdate\entity\object\ItemEntity;
use NetherUpdate\item\ItemFactory;
use NetherUpdate\block\BlockFactory;
use NetherUpdate\libs\muqsit\invmenu\InvMenuHandler;

use pocketmine\Player;
use pocketmine\command\{Command, CommandSender};
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
class Main extends PluginBase{
    use SingletonTrait;
    private static $registered = false;
    private static function registerRuntimeIds(): void{
        if(self::$registered) return;
        self::$registered = true;
        
        $nameToLegacyMap = json_decode(file_get_contents(Server::getInstance()->getResourcePath() . "vanilla/block_id_map.json"), true);
        $metaMap = [];

        /** @see RuntimeBlockMapping::getBedrockKnownStates() */
        foreach(RuntimeBlockMapping::getBedrockKnownStates() as $runtimeId => $state){
            $name = $state->getString("name");
            if(!isset($nameToLegacyMap[$name]))
                continue;

            $legacyId = $nameToLegacyMap[$name];
            if(!isset($metaMap[$legacyId])){
                $metaMap[$legacyId] = 0;
            }

            $meta = $metaMap[$legacyId]++;
            if($meta > 0xf)
                continue;

            /** @see RuntimeBlockMapping::registerMapping() */
            Utils::invoke(RuntimeBlockMapping::class, 'registerMapping', $runtimeId, $legacyId, $meta);
        }
        
    }
    public function onLoad(){
        self::setInstance($this);
        
        self::registerRuntimeIds();
        Tile::registerTile(Lodestone::class);
        Tile::registerTile(Placeholder::class);
        Entity::registerEntity(Arrow::class, false, ["Arrow", "minecraft:arrow"]);
        Entity::registerEntity(SplashPotion::class, false, ['ThrownPotion', 'minecraft:potion', 'thrownpotion']);
        Entity::registerEntity(ItemEntity::class, false, ["Item", "minecraft:item"]);
        Effect::registerEffect(new Effect(27, "%potion.slowFalling", new Color(0xf7, 0xf8, 0xe0)));
        Enchantment::init();
        BlockFactory::init();
        ItemFactory::init();
    }
    public function onEnable(){
        ItemFactory::initCreativeItems();
		if(!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
        $this->getServer()->getCommandMap()->register("NetherUpdate", new SmithCommand);
        $this->getServer()->getCommandMap()->register("NetherUpdate", new RegisterCommand);
        $this->getServer()->getCommandMap()->register("NetherUpdate", new class("skin", "Steal someone skin.", "Usage: /skin <player: name>") extends Command{
            public function __construct(...$args){
                parent::__construct(...$args);
                $this->setPermission("pocketmine.command.skin");
            }
            public function execute(CommandSender $sender, string $commandLabel, array $args){
                if(!$this->testPermission($sender)){
			        return true;
                }
                if($sender instanceof Player){
                    $sender->setSkin(($sender->getServer()->getPlayer($args[0] ?? '') ?? $sender)->getSkin());
                    $sender->sendSkin();
                }
            }
        });
        $this->getServer()->getCommandMap()->register("NetherUpdate", new class("offhand", "Switch the item you are holding into your offhand.") extends Command{
            public function execute(CommandSender $sender, string $commandLabel, array $args){
                if($sender instanceof Player){
                    $mainItem = $sender->getInventory()->getItemInHand();
                    $sender->getInventory()->setItemInHand(($sender instanceof ModPlayer ? $sender->getOffhandInventory()->getItem(0) : Utils::getOffhandInventory($sender))->getItem(0));
                    ($sender instanceof ModPlayer ? $sender->getOffhandInventory() : Utils::getOffhandInventory($sender))->setItem(0, $mainItem);
                }
            }
        });
        $freecam = new class("freecam", "Same as hack client.") extends Command implements Listener{
            private static $freecam = [];
            public function __construct(...$args){
                parent::__construct(...$args);
                $this->setPermission("pocketmine.command.freecam");
            }
            /**
             * @priority HIGHEST
             * @ignoreCancelled
             */
            public function onDataPacketReceive(DataPacketReceiveEvent $event){
                $player = $event->getPlayer();
                $packet = $event->getPacket();
                if($packet instanceof MovePlayerPacket and isset(self::$freecam[spl_object_hash($player)])){
                    $event->setCancelled();
                }
            }
            public function execute(CommandSender $sender, string $commandLabel, array $args){
                static $players = [];
                if(!$this->testPermission($sender)){
			        return true;
                }
                if($sender instanceof Player){
                    $objectHash = spl_object_hash($sender);
                    if(isset(self::$freecam[$objectHash])){
                        $sender->setFlying($players[$objectHash]['isFlying']);
                        $sender->setAllowFlight($players[$objectHash]['allowFlight']);
                        [$sender->keepMovement, $sender->onGround] = [
                            $players[$objectHash]['keepMovement'],
                            $players[$objectHash]['onGround']
                        ];
                        unset(self::$freecam[$objectHash]);
                        $sender->sendSettings();
                        $sender->teleport($sender);
                        $sender->sendMessage("§aNo longer in a freecam session!");
                    }else{
                        $players[$objectHash] = [
                            "isFlying" => $sender->isFlying(),
                            "allowFlight" => $sender->getAllowFlight(),
                            "no-clip" => $sender->isSpectator(),
                            "keepMovement" => $sender->keepMovement,
                            "onGround" => $sender->onGround
                        ];
                        $sender->setFlying(true);
                        $sender->setAllowFlight(true);
			            $sender->keepMovement = true;
			            $sender->onGround = false;

			            //TODO: HACK! this syncs the onground flag with the client so that flying works properly
			            //this is a yucky hack but we don't have any other options :(
			            $sender->sendPosition($sender, null, null, MovePlayerPacket::MODE_TELEPORT);
			            $pk = new AdventureSettingsPacket();

		                $pk->setFlag(AdventureSettingsPacket::NO_CLIP, true);

		                $pk->commandPermission = ($sender->isOp() ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
		                $pk->playerPermission = ($sender->isOp() ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER);
		                $pk->entityUniqueId = $sender->getId();
		                
		                $sender->dataPacket($pk);
                        $sender->sendMessage("§aSuccessfully in a freecam session!");
                        self::$freecam[$objectHash] = true;
                    }
                }
            }
        };
        $this->getServer()->getCommandMap()->register("NetherUpdate", $freecam);
        
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand('version'));
        
        $this->getServer()->getPluginManager()->registerEvents($freecam, $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener, $this);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function(): void{
            foreach(Server::getInstance()->getOnlinePlayers() as $player){
                if($player->getArmorInventory()->getHelmet() instanceof TurtleHelmet and
                !($player->isUnderwater() and !$player->getGenericFlag(Entity::DATA_FLAG_GLIDING))){
                    $player->addEffect(new EffectInstance(Effect::getEffect(Effect::WATER_BREATHING), 200, 0, false));
                }
                if($player->getGenericFlag(Entity::DATA_FLAG_GLIDING) and $player->isSurvival()){
                    if(Server::getInstance()->getTick()%20 == 0){
                        $armor = $player->getArmorInventory();
                        $elytra = $armor->getChestplate();
                        $elytra->applyDamage(1);
                        $armor->setChestplate($elytra);
                    }
                }
                if($player->getGenericFlag(Entity::DATA_FLAG_GLIDING) and $player->getPitch() >= -59 and $player->getPitch() <= 38){
                    $player->resetFallDistance();
                }
                if(($item = $player->getInventory()->getItemInHand()) instanceof LodestoneCompass and
                ($handle = $item->getTrackingHandle()) > 0 and
                ($tile = Utils::searchLodestone($handle, $item->getNamedTagEntry("level"))) instanceof Lodestone){
                    $player->sendPopup("X: $tile->x, Y: $tile->y, Z: $tile->z, Level: {$tile->level->getName()}");
                }
            }
        }), 1);
    }
}