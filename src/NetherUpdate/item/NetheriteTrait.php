<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\{Server, Player};
use pocketmine\nbt\tag\StringTag;
use NetherUpdate\Utils;
trait NetheriteTrait{
    public function applyDamage(int $damage): bool{
        $player = $this->getHolder();
        if(($lastDamage = $player->getLastDamageCause()) === null or !Utils::isCauseFire($lastDamage->getCause())){
            return parent::applyDamage($damage);
        }
        return false;
    }
    public function setHolder(Player $player): self{
        $tag = $this->getNamedTag();
        $tag->setString("holder", $player->getName());
        $this->setNamedTag($tag);
        return $this;
    }
    public function getHolder(): ?Player{
        if(!$this->getNamedTag()->hasTag("holder", StringTag::class)) return null;
        return Server::getInstance()->getPlayer($this->getNamedTag()->getString("holder"));
    }
}