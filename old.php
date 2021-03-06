<?php

/*
__PocketMine Plugin__
name=HungerGames
description=HungerGames Plugin
version=1.0.0
author=sekjun9878
class=HungerGames
apiversion=8,9
*/
		
class HungerGames implements Plugin{	
	private $api;
	
	private $minute = 30;
	private $playerspawncount = 0;
	
	private $gamestarted = false;
	
	private $server;
	private $servname;
	
	private $spawn_loc = array(
	array(170.5, 72, 170.5),
	array(170.5, 72, 163.5),
	array(175.5, 72, 157.5),
	array(180.5, 72, 152.5),
	array(188.5, 72, 152.5),
	array(196.5, 72, 170.5),
	array(201.5, 72, 157.5),
	array(206.5, 72, 163.5),
	array(206.5, 72, 170.5),
	array(206.5, 72, 177.5),
	array(201.5, 72, 183.5),
	array(195.5, 72, 188.5),
	array(188.5, 72, 188.5),
	array(180.5, 72, 188.5),
	array(175.5, 72, 183.5),
	array(170.5, 72, 177.5)
	);
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->server = ServerAPI::request();
	}
	
	public function init(){
	
		$this->api->console->register("hg", "Hunger Games v1.0.0", array($this, "commandHandler"));
		
		$this->api->addHandler("player.join", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.death", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.spawn", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.respawn", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.connect", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.block.touch", array($this, "eventHandler"), 100);
		$this->api->addHandler("entity.health.change", array($this, "eventHandler"), 100);
		
		$this->api->level->loadLevel("HungerGames");
		
		$this->api->schedule(1200, array($this, "minuteSchedule"), array(), true);
		
		$this->api->level->get("HungerGames")->setSpawn(new Vector3(188.5, 75, 170));
		
		$this->servname = $this->server->name;
		$this->server->name = "[OPEN] ".$this->servname;
	}
	
	private function getNextSpawn()
	{
		$this->playerspawncount = $this->playerspawncount + 1;
		if($this->playerspawncount <= 16)
			return $this->spawn_loc[$this->playerspawncount-1];
		else
		{
			$this->playerspawncount = 1;
			return $this->spawn_loc[$this->playerspawncount-1];
		}
	}
	
	public function eventHandler($data, $event)
	{
		switch($event)
		{
			case "player.join":
				if($this->gamestarted == true)
				{
					$data->close();
				}
				break;
			case "player.respawn":
			case "player.death":
				if($this->gamestarted == true)
				{
					$data['player']->close();
				}
				if(count($this->api->player->getAll()) <= 1)
				{
					$this->api->chat->broadcast("Game ended. Server reboot / cleanup...");
					$this->api->console->run("stop");
				}
				break;
			case 'player.connect':
				if($this->gamestarted === true)
				{
					return false;
				}
				break;
			case 'player.block.touch':
				if($this->gamestarted === false)
				{
					return false;
				}
				break;
			case 'entity.health.change':
				if($this->gamestarted === false)
				{
					return false;
				}
				break;
			case "player.spawn":
				if($this->gamestarted === true)
				{
					$data->close("The Game has already started!", false);
					break;
				}
				
				$nextspawn = $this->getNextSpawn();
				$data->teleport(new Vector3($nextspawn[0], $nextspawn[1], $nextspawn[2]));
				$data->blocked = true;
				
				$data->sendChat("----------------------------------------------------");
				$data->sendChat("** Welcome to Hunger Games v1");
				$data->sendChat("** Current Players: ".count($this->api->player->getAll())."/".$this->server->maxClients);
				$data->sendChat("** Have Fun and Good Luck!");
				$data->sendChat("** [NOTE] You are supposed to be stuck before game start.");
				$data->sendChat("----------------------------------------------------");
				break;
		}
	}
	
	private function startGame()
	{		
		$this->gamestarted = true;
		$this->server->name = "[IN PROGRESS] ".$this->servname;
		$this->playerspawncount = 0;
		foreach($this->api->player->getAll() as $p)
		{
			$nextspawn = $this->getNextSpawn();
			$p->teleport(new Vector3($nextspawn[0], $nextspawn[1], $nextspawn[2]));
			
			$p->setGamemode(0);
			
			$p->blocked = false;
		}
	}	

	public function minuteSchedule()
	{
		$this->minute--;
		if($this->minute > 25 and $this->minute <= 30)
		{
			$this->api->chat->broadcast("----------------------------------------------------");
			$this->api->chat->broadcast("** Welcome to Hunger Games v1");
			$this->api->chat->broadcast("** Current Players: ".count($this->api->player->getAll())."/".$this->server->maxClients);
			$this->api->chat->broadcast("** Have Fun and Good Luck!");
			$this->api->chat->broadcast("** [NOTE] You are supposed to be stuck before game start.");
			$this->api->chat->broadcast("** ".($this->minute-25)." minutes left until the game starts.");
			$this->api->chat->broadcast("----------------------------------------------------");
		}
		if($this->minute == 25)
		{
			$this->api->chat->broadcast("** The Game starts NOW");
			$this->api->chat->broadcast("** The Game starts NOW");
			$this->api->chat->broadcast("** The Game starts NOW");
			$this->startGame();
		}
		if($this->minute < 25 and $this->minute > 1)
		{
			$this->api->chat->broadcast(($this->minute)." minutes left");
		}
		if($this->minute == 1)
		{
			$this->api->chat->broadcast("1 minutes left. Spawning everyone to the spawn. Finish the game off!");
			foreach($this->api->player->getAll() as $p)
			{
				$this->playerspawncount = 0;
				$nextspawn = $this->getNextSpawn();
				$p->teleport(new Vector3($nextspawn[0], $nextspawn[1], $nextspawn[2]));
			}
		}
		if($this->minute == 0)
		{
			$this->api->chat->broadcast("Game ended. Server reboot / cleanup...");
			$this->api->console->run("stop");
		}
	}
	
	public function commandHandler($cmd, $params, $issuer, $alias){
		$output = "";
		if($cmd != "hg")
		{
			$output .= "Called via wrong command. Exiting..";
			return $output;
		}
			
		if($issuer instanceof Player)
		{
			$output .= "Command can only be run by console. Exiting...";
			return $output;
		}
			
		switch(array_shift($params)){
			case "settimer":
				$this->minute = array_shift($params);
				$output .= "Success";
				break;
			case "gettimer":
				$output .= $this->minute."\n";
				break;
		}
		return $output;
	}
	
	public function __destruct()
	{
		
	}

}
