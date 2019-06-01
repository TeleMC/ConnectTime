<?php
namespace ConnectTime;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class EventListener implements Listener {

    public function __construct(ConnectTime $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $ev) {
        if ($this->plugin->getTime($ev->getPlayer()->getName()) == null)
            $this->plugin->data[mb_strtolower($ev->getPlayer()->getName())] = 0;
    }
}
