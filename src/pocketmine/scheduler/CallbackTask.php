<?php

namespace pocketmine\scheduler;

class CallbackTask extends Task {

	/** @var callable */
	protected $callable;

	/** @var array */
	protected $args;

	/**
	 * @param callable $callable
	 * @param array    $args
	 */
	public function __construct(callable $callable, array $args = []){
		$this->callable = $callable;
		$this->args = $args;
		$this->args[] = $this;
	}

	/**
	 * @return callable
	 */
	public function getCallable(){
		return $this->callable;
	}

	/**
	 * @param $currentTicks
	 */
	public function onRun($currentTicks){
		call_user_func_array($this->callable, $this->args);
	}

}
