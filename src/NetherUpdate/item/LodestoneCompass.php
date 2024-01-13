<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
class LodestoneCompass extends Item{
    public function __construct(int $meta = 0){
        parent::__construct(ItemIds::LODESTONECOMPASS, $meta, 'Lodestone Compass');
    }
    public function getMaxStackSize(): int{
        return 1;
    }
    public function getTrackingHandle(): int{
        return $this->getNamedTag()->getInt('trackingHandle', 0);
    }
    public function setTrackingHandle(int $handle): self{
        $this->setNamedTagEntry(new IntTag('trackingHandle', $handle));
        return $this;
    }
}