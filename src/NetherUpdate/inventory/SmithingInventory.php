<?php
declare(strict_types = 1);
namespace NetherUpdate\inventory;
use pocketmine\level\Position;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
class SmithingInventory extends ContainerInventory{
    protected $holder;
    public function __construct(Position $pos){
        parent::__construct($pos->asPosition());
    }
    public function getHolder(): Position{
        return $this->holder;
    }
    public function getNetworkType(): int{
        return WindowTypes::SMITHING_TABLE;
    }
    public function getDefaultSize(): int{
        return 3;
    }
    public function getName(): string{
        return "Smithing Table";
    }
}