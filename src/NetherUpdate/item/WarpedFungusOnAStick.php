<?php
declare(strict_types = 1);
namespace NetherUpdate\item;
use pocketmine\item\Tool;
class WarpedFungusOnAStick extends Tool{
    public function __construct(int $meta = 0){
		parent::__construct(ItemIds::WARPED_FUNGUS_ON_A_STICK, $meta, "Warped Fungus on a Stick");
	}
	public function getMaxDurability() : int{
		return 101;
	}
}