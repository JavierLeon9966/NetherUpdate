<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Tool;
class Elytra extends Tool{
    use AEquipTrait;
    public function __construct(int $meta = 0){
        $this->slot = 1;
        parent::__construct(self::ELYTRA, $meta, "Elytra");
    }
    public function getMaxDurability(): int{
        return 433;
    }
}