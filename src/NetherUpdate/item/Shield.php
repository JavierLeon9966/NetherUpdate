<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Tool;
class Shield extends Tool{
    public function __construct(){
        parent::__construct(self::SHIELD, 0, "Shield");
    }
    public function getMaxDurability(): int{
        return 338;
    }
}