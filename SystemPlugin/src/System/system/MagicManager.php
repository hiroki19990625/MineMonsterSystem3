<?php
/*
 _  _ ___ ___  ___  _  _____
| || |_ _| _ \/ _ \| |/ /_ _|
| __ || ||   / (_) | ' < | |
|_||_|___|_|_\\___/|_|\_\___|

 ___ _   _   _  ___ ___ _  _
| _ \ | | | | |/ __|_ _| \| |
|  _/ |_| |_| | (_ || || .` |
|_| |____\___/ \___|___|_|\_|

*/
namespace System\system;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\item\Item;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;

use System\Main;

use System\system\SystemText;

class MagicManager{

	private $main;

	public function __construct(Main $main){

		$this->main = $main;
		$this->text = new SystemText();

	}
	
	public function getData(int $id = 0) : array{
	
		$magic = [//name, attribute, damage, speed, mp, Item, typeID
					0 => ["設定無し", 0, 0, 0, 0, Item::get(0)],
					1 => ["ファイア", 1, 10, 1, 5, Item::get(51,0,1)],
					2 => ["ファイラ", 1, 25, 1, 12, Item::get(51,0,32)],
					3 => ["ファイガ", 1, 38, 1, 22, Item::get(51,0,64)],
					4 => ["ブリザド", 2, 12, 1, 6, Item::get(79,0,1)],
					5 => ["ブリザラ", 2, 28, 1, 14, Item::get(79,0,32)],
					6 => ["ブリザガ", 2, 41, 1, 25, Item::get(79,0,64)],
					7 => ["エアロ", 3, 11, 1, 5, Item::get(287,0,1)],
					8 => ["エアロラ", 3, 26, 1, 13, Item::get(287,0,32)],
					9 => ["エアロガ", 3, 39, 1, 23, Item::get(287,0,64)],
					10 => ["ウォータ", 4, 9, 1, 4, Item::get(9,0,1)],
					11 => ["ウォタラ", 4, 23, 1, 10, Item::get(9,0,32)],
					12 => ["ウォータガ", 4, 35, 1, 20, Item::get(9,0,64)],
					13 => ["バイオ", 30, 6, 1, 4, Item::get(351,2,1)],
					14 => ["バイオガ", 30, 22, 1, 30, Item::get(351,2,64)]
				];
				
		if(!isset($magic[$id])){
			$data = ["存在しません", 0, "-", "-", "-", Item::get(0)];
		}else{
			$data = $magic[$id];
		}

		return $data;
	
	}
	
	public function listMagic($player, $page){
	
		$datas = $data = $this->main->getSave($player->getName())["magiclist"];
		$items = [];
		$c = 0;
		$listp = ($page - 1) * 4;
		
		foreach($datas as $data){
			$items[$c] = $data;
			++$c;
		}
		
		$player->sendMessage("§e=====魔法リスト(" . $c . ")=====");
		
		for($i = 0; $i < 4; ++$i){
			$ind = ($listp + $i);
			if(!isset($items[$ind]))return;
			$db = $this->getData($items[$ind]);
			$player->sendMessage($this->text->systemSpaceText("magic.list", ["id" => $items[$ind], "name" => $db[0]]));
		}
	
	}
	
	private function getAttributeString(int $type = 0) : string{
	
		$attribute = [
						0 => "-----",
						1 => "火属性",
						2 => "氷属性",
						3 => "風属性",
						4 => "水属性",
						30 => "毒属性"
					];
					
		return $attribute[$type];
	}
	
	public function magicInfo(string $name, int $id = 0) : string{
	
		$magic = $this->getData($id);
		
		$att = $this->getAttributeString($magic[1]);
		
		if($this->haveMagic($name, $id)){
			$have = "持っている";
		}else{
			$have = "持っていない";
		}
		
		return $this->text->systemSpaceText("magic.usage", ["name" => $magic[0], "mp" => $magic[4], "att" => $att, "power" => $magic[2], "have" => $have]);
	
	}

	public function magicBehavior(){

	}
	
	public function getInfoMagic(string $name) : string{
	
		$id = $this->getMagic($name);
		$magic = $this->getData($id);
		
		$att = $this->getAttributeString($magic[1]);
		
		return $this->text->systemSpaceText("magic.get", ["name" => $magic[0], "mp" => $magic[4], "att" => $att, "power" => $magic[2]]);
	
	}
	
	public function haveMagic(string $name, int $id) : bool{
	
		$data = $this->main->getSave($name);
		
		foreach($data["magiclist"] as $magic){
		
			if($magic == $id){
				return true;
			}
		
		}
		
		return false;
	
	}
	
	public function shotMagic(Player $player, int $id){
	
		if($id <= 0)return;
	
		$magic = $this->getData($id);
		
		$mpclass = $this->main->getMPClass();
		
		$mp = $mpclass->getMP($player->getName());
		
		if($mp < $magic[4]){
			$player->sendMessage($this->text->systemSpaceText("magic.nomp"));
			return;
		}
		
		$mpclass->useMP($player->getName(), $magic[4]);
		
		$player->sendTip($this->text->systemSpaceText("magic.use", ["name" => $magic[0]]));
		
		if(isset($magic[6])){
			//Todo $this->magicBehavior();
			return;
		}

		$nbt = new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $player->x),
				new DoubleTag("", $player->y + $player->getEyeHeight()),
				new DoubleTag("", $player->z)
					] ),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("",- \sin($player->yaw / 180 * M_PI) *\cos($player->pitch / 180 * M_PI)),
				new DoubleTag("",- \sin($player->pitch / 180 * M_PI)),
				new DoubleTag("",\cos($player->yaw / 180 * M_PI) *\cos($player->pitch / 180 * M_PI))
					] ),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", $player->yaw),
				new FloatTag("", $player->pitch)
					] )
			] );

		$ammo = Entity::createEntity("Magic", $player->chunk, $nbt, $player, true, $magic[5], $magic[2], $magic[1]);
		$ammo->setMotion($ammo->getMotion()->multiply(1));
		$ammo->spawnToAll();

	}
	
	public function getMagicList(string $name){
		
		$data = $this->main->getSave($name);

		if(isset($data)){
			return $data["magiclist"];
		}else{
			return null;
		}
		
	}
	
	public function setMagicList(string $name, array $list) : bool{
	
		$data = $this->main->getSave($name);

		if(isset($data)){
			$data["magiclist"] = $list;
			$this->main->setSave($name, $data);
			return true;
		}else{
			return false;
		}
	
	}
	
	public function addMagicList(string $name, int $id) : bool{
	
		$data = $this->main->getSave($name);
		
		if($this->haveMagic($name, $id)){
			return false;
		}

		if(isset($data)){
			$data["magiclist"][] = $id;
			$this->main->setSave($name, $data);
			$this->checkExp($name);
			return true;
		}else{
			return false;
		}
	
	}
	
	public function fixMagicList(string $name) : bool{
		return false;
	}
	
	public function getMagic(string $name){
	
		$data = $this->main->getSave($name);

		if(isset($data)){
			return $data["magic"];
		}else{
			return null;
		}
	
	}
	
	public function setMagic(string $name, int $id) : bool{
	
		$data = $this->main->getSave($name);

		if(isset($data)){
			$data["magic"] = $id;
			$this->main->setSave($name, $data);
			return true;
		}else{
			return false;
		}
	
	}

}