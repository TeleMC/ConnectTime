<?php
namespace ConnectTime;

use pocketmine\command\{Command, CommandSender};
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class ConnectTime extends PluginBase {

    public $pre = "§e•";

    //public $pre = "§l§e[ §f시스템 §e]§r§e";

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "time.yml", Config::YAML);
        $this->data = $this->config->getAll();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getScheduler()->scheduleRepeatingTask(new TimeTask($this), 20);
    }

    public function onDisable() {
        $this->save();
    }

    public function save() {
        $this->config->setAll($this->data);
        $this->config->save();
    }

    public function reduceTime(string $name, int $amount) {
        $name = mb_strtolower($name);
        if (!isset($this->data[$name]) || $this->data[$name] < $amount)
            $this->data[$name] = 0;
        $this->data[$name] -= $amount;
    }

    public function removeTime(string $name) {
        $name = mb_strtolower($name);
        unset($this->data[$name]);
    }

    public function tick() {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->addTime($player->getName(), 1);
        }
    }

    public function addTime(string $name, int $amount) {
        $name = mb_strtolower($name);
        if (!isset($this->data[$name]))
            $this->data[$name] = 0;
        $this->data[$name] += $amount;
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, $args): bool {
        if ($cmd->getName() == "접속시간") {
            if (!isset($args[0])) {
                $sender->sendMessage("--- 접속시간 도움말 1 / 1 ---");
                if ($sender->isOp()) {
                    $sender->sendMessage("{$this->pre} /접속시간 제거 <플레이어> | 플레이어의 접속시간을 초기화합니다.");
                    $sender->sendMessage("{$this->pre} /접속시간 리셋 | 접속시간을 모두 초기화합니다.");
                }
                $sender->sendMessage("{$this->pre} /접속시간 보기 <플레이어> | 플레이어의 접속시간을 봅니다.");
                $sender->sendMessage("{$this->pre} /접속시간 순위 <인덱스> | 접속시간 순위를 봅니다.");
            } else {
                switch ($args[0]) {
                    case "제거":
                        if (!$sender->isOp())
                            return false;
                        if (!isset($args[1])) {
                            $sender->sendMessage("{$this->pre} 닉네임이 기입되지 않았습니다.");
                            return false;
                        }
                        unset($args[0]);
                        $name = implode(" ", $args);
                        if ($this->getTime($name) == null) {
                            $sender->sendMessage("{$this->pre} 해당 플레이어를 찾아볼 수 없습니다.");
                            return false;
                        }
                        $this->setTime($name, 0);
                        $sender->sendMessage("{$this->pre} {$name}님의 접속시간을 초기화하였습니다.");
                        break;

                    case "리셋":
                        if (!$sender->isOp())
                            return false;
                        else {
                            $this->resetTime();
                            $sender->sendMessage("{$this->pre} 접속시간을 초기화하였습니다.");
                        }
                        break;

                    case "보기":
                        if (!isset($args[1]))
                            $name = $sender->getName();
                        else {
                            unset($args[0]);
                            $name = implode(" ", $args);
                        }
                        $time = $this->getTime($name);
                        if ($time == null) {
                            $sender->sendMessage("{$this->pre} 해당 플레이어를 찾아볼 수 없습니다.");
                            return false;
                        } else
                            $sender->sendMessage("{$this->pre} {$name}님의 접속시간: {$time[0]}시간 {$time[1]}분 {$time[2]}초 | {$this->getRank($name)}위");
                        break;

                    case "순위":
                        if (count($this->data) == 0) {
                            $sender->sendMessage("--- 접속시간 순위 1 / 1 ---");
                            $sender->sendMessage("{$this->pre} 접속한 유저가 존재하지 않습니다.");
                            return true;
                        }
                        $maxpage = ceil(count($this->data) / 5);
                        if (!isset($args[1]) || !is_numeric($args[1]) || $args[1] <= 0) {
                            $page = 1;
                        } elseif ($args[1] > $maxpage) {
                            $page = $maxpage;
                        } else {
                            $page = $args[1];
                        }
                        $list = "";
                        $count = 1;
                        arsort($this->data);
                        foreach ($this->data as $key => $value) {
                            if ($page * 5 - 5 <= $count and $count < $page * 5) {
                                $time = $this->getTime($key);
                                $list .= "§l§e[§f{$count}위§e] §r§e{$key}: {$time[0]}시간 {$time[1]}분 {$time[2]}초\n";
                                $count++;
                            } else {
                                $count++;
                                continue;
                            }
                        }
                        $sender->sendMessage("--- 접속시간 순위 {$page} / {$maxpage} ---");
                        $sender->sendMessage($list);
                        break;

                    default:
                        $sender->sendMessage("--- 접속시간 도움말 1 / 1 ---");
                        if ($sender->isOp()) {
                            $sender->sendMessage("{$this->pre} /접속시간 제거 <플레이어> | 플레이어의 접속시간을 초기화합니다.");
                            $sender->sendMessage("{$this->pre} /접속시간 리셋 | 접속시간을 모두 초기화합니다.");
                        }
                        $sender->sendMessage("{$this->pre} /접속시간 보기 <플레이어> | 플레이어의 접속시간을 봅니다.");
                        $sender->sendMessage("{$this->pre} /접속시간 순위 <인덱스> | 접속시간 순위를 봅니다.");
                        break;
                }
                return true;
            }
            return false;
        }
        return false;
    }

    public function getTime(string $name) {
        $name = mb_strtolower($name);
        if (!isset($this->data[$name]))
            return null;
        else {
            $time = $this->data[$name];
            $hour = (int) ($time / 60 / 60);
            $minute = (int) (($time / 60)) - ($hour * 60);
            $second = (int) $time - (($hour * 60 * 60) + ($minute * 60));
            return [$hour, $minute, $second];
        }
    }

    public function setTime(string $name, int $amount) {
        $name = mb_strtolower($name);
        $this->data[$name] = $amount;
    }

    public function resetTime() {
        foreach ($this->data as $key => $value) {
            $this->data[$key] = 0;
        }
    }

    public function getRank(string $name) {
        $name = mb_strtolower($name);
        if (!isset($this->data[$name]))
            return null;
        else {
            arsort($this->data);
            $i = 1;
            foreach ($this->data as $key => $value) {
                if ($key == $name)
                    break;
                else
                    $i++;
            }
            return $i;
        }
    }
}
