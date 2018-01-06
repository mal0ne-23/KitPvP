<?php namespace kitpvp\arena\spectate;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\entity\Entity;

use kitpvp\KitPvP;
use kitpvp\arena\spectate\uis\SpectateChooseUi;

class Spectate{

	public $plugin;

	public $spectating = [];

	public function __construct(KitPvP $plugin){
		$this->plugin = $plugin;
	}

	public function tick(){
		foreach($this->spectating as $name => $tick){
			$player = $this->plugin->getServer()->getPlayerExact($name);
			if($player instanceof Player){
				if($tick != -1){
					if($tick >= 40){
						$this->spectating[$name] = -1;
						$player->showModal(new SpectateChooseUi());
					}else{
						$this->spectating[$name]++;
					}
				}
			}else{
				unset($this->spectating[$name]);
			}
		}
	}

	public function isSpectating(Player $player){
		return isset($this->spectating[$player->getName()]);
	}

	public function setSpectating(Player $player){
		$this->plugin->getKits()->getSession($player)->removeKit();

		$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
		$player->setAllowFlight(true);
		$player->setFlying(true);

		$player->addTitle("You died!", "Spectating...", 5, 30, 5);

		$this->spectating[$player->getName()] = 0;
		$player->setMotion($player->getMotion()->add(0,0.5));

		//$options = Item::get(Item::PAPER, 0, 1);
		//$options->setCustomName("Spectator options");

		$compass = Item::get(Item::COMPASS);
		$compass->setCustomName("Track players");

		$star = Item::get(Item::NETHER_STAR);
		$star->setCustomName("Play again!");

		$bed = Item::get(Item::BED);
		$bed->setCustomName("Leave spectator mode");

		
		//$player->getInventory()->setItem(1, $options);
		$player->getInventory()->setItem(4, $compass);
		$player->getInventory()->setItem(1, $star);
		$player->getInventory()->setItem(8, $bed);
	}

	public function removeSpectating(Player $player){
		unset($this->spectating[$player->getName()]);
		$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
		$player->setGamemode(1); $player->setGamemode(0);
	}

}