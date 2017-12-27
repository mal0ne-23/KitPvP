<?php namespace kitpvp\kits\components;

use pocketmine\event\Listener;
use pocketmine\event\player\{
	PlayerMoveEvent,
	PlayerQuitEvent
};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent,
	EntityDamageByChildEntityEvent,
	EntityShootBowEvent
};
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

use pocketmine\entity\{
	Entity,
	Effect,
	Arrow
};
use pocketmine\level\sound\{
	EndermanTeleportSound
};
use pocketmine\item\Item;

use kitpvp\KitPvP;
use kitpvp\kits\Kits;
use kitpvp\kits\event\{
	KitEquipEvent,
	KitUnequipEvent,
	KitReplenishEvent
};

use core\AtPlayer as Player;

class KitPowerListener implements Listener{

	public $plugin;
	public $kits;

	public function __construct(KitPvP $plugin, Kits $kits){
		$this->plugin = $plugin;
		$this->kits = $kits;
	}

	public function onEquip(KitEquipEvent $e){
		$player = $e->getPlayer();
		$kit = $e->getKit();

		if($kit->getName() == "m4l0ne23"){
			$player->setMaxHealth(24);
		}
		$player->setHealth($player->getMaxHealth());

		if($kit->getName() == "scout"){
			$player->setAllowFlight(true);
		}

		foreach($this->kits->kits as $kits){
			if($kits != $kit){
				$kits->subtractPlayerCooldown($player);
			}
		}
	}

	public function onUnequip(KitUnequipEvent $e){
		$player = $e->getPlayer();
		$player->setMaxHealth(20);
		$player->setGamemode(1); $player->setGamemode(0);

		unset($this->plugin->getCombat()->getSpecial()->special[$player->getName()]);

		$this->kits->getSession($player)->resetAbilityArray();
		$this->kits->setInvisible($player, false); //check might make invalid..?

		$player->getInventory()->clearAll();
	}

	// Powers and shit below \\
	public function onMove(PlayerMoveEvent $e){
		$player = $e->getPlayer();
		$from = $e->getFrom();
		$to = $e->getTo();
		$session = $this->kits->getSession($player);
		if($session->hasKit()){
			$kit = $session->getKit();
			switch($kit->getName()){
				case "spy":
					//Stealth Mode
					if(isset($session->ability["still"])){
						if($player->getFloorX() != $session->ability["still"][1] || $player->getFloorZ() != $session->ability["still"][3]){
							unset($session->ability["still"]);
							if(!$player->isSneaking()){
								if($this->kits->isInvisible($player)){
									$this->kits->setInvisible($player, false);
								}
							}
						}
					}
				break;
			}
		}
	}

	public function onQuit(PlayerQuitEvent $e){
		$player = $e->getPlayer();
	}

	public function onData(DataPacketReceiveEvent $e){
		$player = $e->getPlayer();
		$packet = $e->getPacket();
		$kits = $this->kits;
		if($packet instanceof PlayerActionPacket){
			$action = $packet->action;
			$session = $this->kits->getSession($player);
			if($session->hasKit()){
				$kit = $session->getKit();
				switch($kit->getName()){
					case "spy":
						switch($action){
							case PlayerActionPacket::ACTION_START_SNEAK:
								//Stealth Mode
								if(!$this->plugin->getCombat()->getLogging()->inCombat($player)){
									$kits->setInvisible($player, true);
								}
							break;
							case PlayerActionPacket::ACTION_STOP_SNEAK:
								//Stealth Mode
								if($kits->isInvisible($player)){
									$kits->setInvisible($player, false);
								}
							break;
						}
					break;
				}
			}
		}
	}

	public function onDmg(EntityDamageEvent $e){
		if($e->isCancelled()) return;

		$player = $e->getEntity();
		$kits = $this->plugin->getKits();
		$teams = $this->plugin->getCombat()->getTeams();
		if($player instanceof Player){
			if($e instanceof EntityDamageByEntityEvent){
				$killer = $e->getDamager();
				if($killer instanceof Player){
					$session = $this->kits->getSession($player);
					if($session->hasKit()){
						$kit = $session->getKit();
						switch($kit->getName()){
							case "witch":
								//Curse
								$chance = mt_rand(1,100);
								if($chance <= 1){
									$killer->addEffect(Effect::getEffect(Effect::POISON)->setDuration(20 * 4)->setAmplifier(2));
								}
							break;
							case "spy":
								//Last Chance
								if(!isset($session->ability["last_chance"])){
									if(($player->getHealth() - $e->getFinalDamage()) <= 5){
										$player->addEffect(Effect::getEffect(Effect::BLINDNESS)->setDuration(20 * 5));
										$kits->setInvisible($player, true);
										foreach($player->getLevel()->getPlayers() as $p){
											if($p->distance($player) <= 4 && $p != $player){
												$dv = $p->getDirectionVector();
												$p->knockback($p, 0 -$dv->x, -$dv->z, 0.8);
											}
										}
										$session->ability["last_chance"] = time();
									}
								}else{
									if($kits->isInvisible($player)){
										$kits->setInvisible($player, false);
									}
								}
							break;
							case "scout":
								//Bounceback
								$chance = mt_rand(1,100);
								if($chance <= 25){
									$dv = $killer->getDirectionVector();
									$killer->knockback($killer, 0 -$dv->x, -$dv->z, 0.45);
								}
							break;
							case "assault":
								//Adrenaline
								if(!isset($session->ability["adrenaline"])){
									if(($player->getHealth() - $e->getFinalDamage()) <= 5){
										$player->removeEffect(Effect::SPEED);
										$player->addEffect(Effect::getEffect(Effect::JUMP)->setAmplifier(2)->setDuration(20 * 10));
										$player->addEffect(Effect::getEffect(Effect::SPEED)->setAmplifier(4)->setDuration(20 * 10));
										$player->setHealth(15);
										$session->ability["adrenaline"] = time();
									}
								}
							break;
							case "medic":
								//Miracle
								if(!isset($session->ability["miracle"])){
									if(($player->getHealth() - $e->getFinalDamage()) <= 5){
										$player->setHealth($player->getHealth() + 5);
										$session->ability["miracle"] = true;
									}
								}
							break;
							case "enderman":
								//Slender
								if(!isset($session->ability["slender"])){
									if(($player->getHealth() - $e->getFinalDamage()) <= 5){
										$player->addEffect(Effect::getEffect(Effect::INVISIBILITY)->setDuration(20 * 5));
										$player->getLevel()->addSound(new EndermanTeleportSound($player));
										foreach($player->getLevel()->getPlayers() as $p){
											if($p->distance($player) <= 4 && $p != $player){
												$dv = $p->getDirectionVector();
												$p->knockback($p, 0 -$dv->x, -$dv->z, 0.8);
												$p->addEffect(Effect::getEffect(Effect::BLINDNESS)->setDuration(20 * 7));
											}
										}
										$session->ability["slender"] = time();
									}
								}
								//Arrow Dodge
								if($e instanceof EntityDamageByChildEntityEvent){
									$child = $e->getChild();
									if($child instanceof Arrow){
										$chance = mt_rand(0,100);
										if($chance <= 25){
											$e->setCancelled();
											$player->getLevel()->addSound(new EndermanTeleportSound($player));
										}
									}
								}
							break;
							case "m4l0ne23":
								//Bounceback
								$chance = mt_rand(1,100);
								if($chance <= 25){
									$dv = $killer->getDirectionVector();
									$killer->knockback($killer, 0 -$dv->x, -$dv->z, 0.45);
								}
							break;
						}
					}
					$session = $this->kits->getSession($killer);
					if($session->hasKit()){
						$kit = $session->getKit();
						switch($kit->getName()){
							case "spy":
								//Stealth Mode
								if($kits->isInvisible($killer)){
									$kits->setInvisible($killer, false);
								}
							break;
							case "medic":
								//Life Steal
								if($e->getFinalDamage() >= $player->getHealth()){
									$killer->setHealth(($killer->getHealth() + 5 >= $killer->getMaxHealth() ? $killer->getMaxHealth() : $killer->getHealth() + 5));
								}
							break;
						}
					}
				}
			}
		}
	}

	public function onBow(EntityShootBowEvent $e){
		$player = $e->getEntity();
		$force = $e->getForce();
		$dv = $player->getDirectionVector();
		$session = $this->kits->getSession($player);
		if($session->hasKit()){
			$session->addBowShot();
			$shots = $session->getBowShots();
			if($shots >= 10){
				$as = $this->plugin->getAchievements()->getSession($player);
				if(!$as->hasAchievement("faker")) $as->get("faker");
			}
			$kit = $session->getKit();
			//Aim Assist
			if($kit->getName() == "archer"){
				if(isset($session->ability["aim_assist"])){
					unset($session->ability["aim_assist"]);
				}
			}
		}
	}

}