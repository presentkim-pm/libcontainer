<?php

declare(strict_types=1);

namespace kim\present\libcontainerspike;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use function array_slice;
use function count;
use function implode;
use function is_numeric;
use function spl_object_id;
use function sprintf;

final class Main extends PluginBase implements Listener{

	private Options $opt;

	/** @var array<int, true> spl_object_id(InventoryManager) => hooked */
	private array $hooked = [];
	/** @var array<int, int> spl_object_id(Player) => fake actor id */
	private array $fakeIds = [];

	protected function onEnable() : void{
		$this->opt = new Options();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$this->hook($p);
		}
	}

	private function log(string $msg) : void{
		if($this->opt->log){
			$this->getLogger()->info($msg);
		}
	}

	private function say(Player $p, string $msg) : void{
		$this->log("[" . $p->getName() . "] " . $msg);
		if($this->opt->log && $p->isConnected()){
			$p->sendMessage("§7[lcs] " . $msg);
		}
	}

	private function hook(Player $player) : void{
		$mgr = $player->getNetworkSession()->getInvManager();
		if($mgr === null){
			return;
		}
		$key = spl_object_id($mgr);
		if(isset($this->hooked[$key])){
			return;
		}
		$this->hooked[$key] = true;
		$mgr->getContainerOpenCallbacks()->add(function(int $id, Inventory $inv) use ($player) : ?array{
			if(!$inv instanceof SpikeInventory){
				return null;
			}
			return $this->buildOpenPackets($player, $id, $inv);
		});
	}

	/** @return list<ClientboundPacket> */
	private function buildOpenPackets(Player $player, int $windowId, SpikeInventory $inv) : array{
		$o = $this->opt;
		$this->removeFake($player); //stale one, if any
		$actorId = Entity::nextRuntimeId();
		$this->fakeIds[spl_object_id($player)] = $actorId;

		$pos = $player->getEyePos()->addVector($player->getDirectionVector()->multiply($o->distance))->add(0, $o->dy, 0);

		$meta = new EntityMetadataCollection();
		$meta->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
		if(!$o->visible){
			$meta->setGenericFlag(EntityMetadataFlags::INVISIBLE, true);
		}
		$meta->setString(EntityMetadataProperties::NAMETAG, $inv->title);
		$meta->setFloat(EntityMetadataProperties::SCALE, $o->scale);
		if($o->scale < 0.5){
			$meta->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
			$meta->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
		}
		$meta->setByte(EntityMetadataProperties::CONTAINER_TYPE, $inv->windowType);
		$meta->setInt(EntityMetadataProperties::CONTAINER_BASE_SIZE, $inv->getSize());

		$add = AddActorPacket::create(
			$actorId,
			$actorId,
			$inv->entityType,
			$pos,
			null,
			0.0,
			$player->getLocation()->yaw,
			$player->getLocation()->yaw,
			$player->getLocation()->yaw,
			[],
			$meta->getAll(),
			new PropertySyncData([], []),
			[],
			null
		);
		$open = ContainerOpenPacket::entityInv($windowId, $inv->windowType, $actorId);
		$this->say($player, sprintf(
			"send AddActor type=%s id=%d pos=%.2f,%.2f,%.2f scale=%s visible=%s; ContainerOpen win=%d type=%d size=%d",
			$inv->entityType, $actorId, $pos->x, $pos->y, $pos->z, $o->scale, $o->visible ? "y" : "n",
			$windowId, $inv->windowType, $inv->getSize()
		));
		return [$add, $open];
	}

	private function removeFake(Player $player) : void{
		$k = spl_object_id($player);
		if(isset($this->fakeIds[$k])){
			$id = $this->fakeIds[$k];
			unset($this->fakeIds[$k]);
			if($player->isConnected()){
				$player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($id));
				$this->log("[" . $player->getName() . "] send RemoveActor id=$id");
			}
		}
	}

	public function onJoin(PlayerJoinEvent $e) : void{
		$this->hook($e->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $e) : void{
		unset($this->fakeIds[spl_object_id($e->getPlayer())]);
	}

	public function onClose(InventoryCloseEvent $e) : void{
		$inv = $e->getInventory();
		if($inv instanceof SpikeInventory){
			$this->say($e->getPlayer(), "InventoryCloseEvent (server side window closed)");
			$this->removeFake($e->getPlayer());
		}
	}

	public function onRecv(DataPacketReceiveEvent $e) : void{
		$pk = $e->getPacket();
		if($pk instanceof ContainerClosePacket){
			$p = $e->getOrigin()->getPlayer();
			$note = ($pk->windowId === -1 || $pk->windowId === 255) ? " (id -1/255: client REJECTED/could not show window?)" : "";
			$msg = "recv ContainerClose windowId={$pk->windowId} type={$pk->windowType} server={$pk->server}" . $note;
			if($p !== null){
				if($p->getCurrentWindow() instanceof SpikeInventory){
					$this->say($p, $msg);
				}
			}else{
				$this->log($msg);
			}
		}
	}

	/** @priority HIGHEST */
	public function onTransaction(InventoryTransactionEvent $e) : void{
		$tx = $e->getTransaction();
		$actions = $tx->getActions();
		$involved = false;
		foreach($actions as $a){
			if($a instanceof SlotChangeAction && $a->getInventory() instanceof SpikeInventory){
				$involved = true;
				break;
			}
		}
		if(!$involved){
			return;
		}
		$p = $tx->getSource();
		$this->say($p, "--- transaction: " . count($actions) . " action(s) ---");
		foreach($actions as $a){
			if($a instanceof SlotChangeAction){
				$inv = $a->getInventory();
				$this->say($p, sprintf(
					"  SlotChange %s slot=%d src=[%s] tgt=[%s]%s",
					(new \ReflectionClass($inv))->getShortName(), $a->getSlot(),
					self::itemStr($a->getSourceItem()), self::itemStr($a->getTargetItem()),
					$inv instanceof SpikeInventory ? " <== SPIKE" : ""
				));
			}else{
				$this->say($p, "  " . (new \ReflectionClass($a))->getShortName() .
					" src=[" . self::itemStr($a->getSourceItem()) . "] tgt=[" . self::itemStr($a->getTargetItem()) . "]");
			}
		}
		$e->cancel();
	}

	private static function itemStr(Item $i) : string{
		return $i->isNull() ? "air" : $i->getName() . " x" . $i->getCount();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage("in-game only");
			return true;
		}
		$this->hook($sender);
		$sub = $args[0] ?? "";
		switch($sub){
			case "open":
				if(count($args) < 3 || !is_numeric($args[2])){
					$sender->sendMessage("/lcs open <entityType> <size> [windowType=0] [title...]");
					return true;
				}
				$size = (int) $args[2];
				if($size < 1 || $size > 256){
					$sender->sendMessage("size 1..256");
					return true;
				}
				$wt = isset($args[3]) && is_numeric($args[3]) ? (int) $args[3] : WindowTypes::CONTAINER;
				$title = count($args) > 4 ? implode(" ", array_slice($args, 4)) : "Spike";
				$inv = new SpikeInventory($size, $args[1], $wt, $title);
				for($i = 0; $i < $size; $i++){
					$inv->setItem($i, VanillaItems::PAPER()->setCustomName("slot $i")->setCount($i % 64 + 1));
				}
				$ok = $sender->setCurrentWindow($inv);
				$this->say($sender, "setCurrentWindow => " . ($ok ? "true" : "false (cancelled)"));
				return true;
			case "opt":
				if(count($args) < 3){
					$sender->sendMessage("/lcs opt <scale|visible|distance|dy> <value>  (now: scale={$this->opt->scale} visible=" . (int) $this->opt->visible . " distance={$this->opt->distance} dy={$this->opt->dy})");
					return true;
				}
				if(!is_numeric($args[2])){
					$sender->sendMessage("value must be numeric");
					return true;
				}
				$v = (float) $args[2];
				switch($args[1]){
					case "scale": $this->opt->scale = $v; break;
					case "visible": $this->opt->visible = $v !== 0.0; break;
					case "distance": $this->opt->distance = $v; break;
					case "dy": $this->opt->dy = $v; break;
					default:
						$sender->sendMessage("unknown key");
						return true;
				}
				$sender->sendMessage("ok: {$args[1]}=$v");
				return true;
			case "log":
				$this->opt->log = !$this->opt->log;
				$sender->sendMessage("logging " . ($this->opt->log ? "ON" : "OFF"));
				return true;
		}
		$sender->sendMessage("/lcs open|opt|log");
		return true;
	}
}
