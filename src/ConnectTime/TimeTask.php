<?php
namespace ConnectTime;

use pocketmine\scheduler\Task;

class TimeTask extends Task {

    public function __construct(ConnectTime $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        $this->plugin->tick();
    }
}
