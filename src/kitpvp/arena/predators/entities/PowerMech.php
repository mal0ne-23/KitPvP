<?php namespace kitpvp\arena\predators\entities;

use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Skin;

class PowerMech extends Boss{

	public $attackDamage = 7;
	public $speed = 0.5;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->setSkin(new Skin("Standard_Custom", file_get_contents("/home/data/skins/powermechboss.dat")));
	}

	public function getType(){
		return "PowerMech";
	}

	public function getReinforcement(Level $level, CompoundTag $nbt){
		if(mt_rand(0,1) == 0){
			return new Cyborg($level, $nbt);
		}else{
			return new Robot($level, $nbt);
		}
	}

}