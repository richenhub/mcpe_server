<?php

declare(strict_types=1);

namespace pocketmine\sleeper;

use function count;
use function microtime;

class SleeperHandler{
	/** @var \Threaded */
	private $sharedObject;

	/**
	 * @var \Closure[]
	 * @phpstan-var array<int, \Closure() : void>
	 */
	private $notifiers = [];

	/** @var int */
	private $nextSleeperId = 0;

	public function __construct(){
		$this->sharedObject = new \Threaded();
	}

	/**
	 * @param \Closure $handler Called when the notifier wakes the server up, of the signature `function() : void`
	 * @phpstan-param \Closure() : void $handler
	 */
	public function addNotifier(SleeperNotifier $notifier, \Closure $handler) : void{
		$id = $this->nextSleeperId++;
		$notifier->attachSleeper($this->sharedObject, $id);
		$this->notifiers[$id] = $handler;
	}

	public function removeNotifier(SleeperNotifier $notifier) : void{
		unset($this->notifiers[$notifier->getSleeperId()]);
	}

	private function sleep(int $timeout) : void{
		$this->sharedObject->synchronized(function(int $timeout) : void{
			if($this->sharedObject->count() === 0){
				$this->sharedObject->wait($timeout);
			}
		}, $timeout);
	}

	public function sleepUntil(float $unixTime) : void{
		while(true){
			$this->processNotifications();

			$sleepTime = (int) (($unixTime - microtime(true)) * 1000000);
			if($sleepTime > 0){
				$this->sleep($sleepTime);
			}else{
				break;
			}
		}
	}

	public function sleepUntilNotification() : void{
		$this->sleep(0);
		$this->processNotifications();
	}

	public function processNotifications() : void{
		while(true){
			$notifierIds = $this->sharedObject->synchronized(function() : array{
				$notifierIds = [];
				foreach($this->sharedObject as $notifierId => $_){
					$notifierIds[$notifierId] = $notifierId;
					unset($this->sharedObject[$notifierId]);
				}
				return $notifierIds;
			});
			if(count($notifierIds) === 0){
				break;
			}
			foreach($notifierIds as $notifierId){
				if(!isset($this->notifiers[$notifierId])){
					continue;
				}
				$this->notifiers[$notifierId]();
			}
		}
	}
}