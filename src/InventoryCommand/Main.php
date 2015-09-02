<?php

namespace InventoryCommand;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use SimpleAuth\event\PlayerAuthenticateEvent;

class Main extends PluginBase implements Listener{
    private $auth;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(TextFormat::GREEN . "InventoryCommand Enabled!");
        $this->saveDefaultConfig();
        $this->auth = $this->getServer()->getPluginManager()->getPlugin("SimpleAuth");
        if(count($this->getConfig()->get("data")) > 35){
            $this->getLogger()->error("Exeption: Number of slots out of range!");
            $this->getServer()->shutdown();
        }
    }

    public function onLoad(){
        $this->getLogger()->info(TextFormat::YELLOW . "Loading InventoryCommand...");
    }

    public function onDisable(){
        $this->getLogger()->info(TextFormat::RED . "InventoryCommand Disabled!");
    }

    private function isAllowedWorld(Level $level){
        $level = strtolower($level->getName());
        $get = $this->getConfig()->get("level");
        if(empty($get) || !$get)
            return true;
        else{
            $e = explode(",", $get);
            if(count($e) > 1){
                foreach($e as $l){
                    if(strtolower(trim($l)) == $level)
                        return true;
                }
                return false;
            }else{
                return $level == strtolower(trim($get));
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function onItemHeld(PlayerItemHeldEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();

        if($player->isCreative())
            return;

        if(!$this->isAllowedWorld($player->getLevel()))
            return;
        
        if($event->isCancelled() || ($this->auth !== null && !$this->auth->isPlayerAuthenticated($player)))
            return;
                
        foreach($this->getConfig()->get("data") as $slot => $g) {
            if ($item->getId() === $g["id"] && $item->getDamage() === $g["damage"]) {
                foreach ($g["command"] as $cmd){
                    $popup = $g["popup"];
                    $player->sendPopup($popup);
                    if(!empty($cmd))
                        $this->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{player}", $player->getName(), str_replace("/", "", $cmd)));
                }
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();

        if($player->isCreative())
            return;
        foreach($this->getConfig()->get("data") as $slot => $g){
            $item = Item::get($g["id"], $g["damage"]);
            if($item->getId() !== Block::AIR && $item->getMaxStackSize() <= $g["amount"]){
                $item->setCount($g["amount"]);
                if(!$player->getInventory()->contains($item) && $player->getInventory()->canAddItem($item)){
  $slot = (int) $slot{strlen($slot) - 1};
                    $player->getInventory()->setItem($slot, $item);
                }
            }
        }
    }
}
