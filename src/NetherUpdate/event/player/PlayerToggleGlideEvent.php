<?php
declare(strict_types = 1);
namespace NetherUpdate\event\player;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerToggleGlideEvent extends PlayerEvent implements Cancellable{
	/** @var bool */
	protected $isGliding;

	public function __construct(Player $player, bool $isGliding){
		$this->player = $player;
		$this->isGliding = $isGliding;
	}

	public function isGliding() : bool{
		return $this->isGliding;
	}
}