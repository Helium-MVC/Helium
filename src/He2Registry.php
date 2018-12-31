<?php

namespace prodigyview\helium;

use prodigyview\design\StaticInstance;

/**
 * The registry acts a way to share resources across the different components through the apps
 * execution.
 *
 * For example, in the registry we can assign a variable when app is initialized, and then call the
 * variables at a different point of execution such as the controller.
 *
 * @package prodigyview\helium
 */
Class He2Registry {
	
	use StaticInstance;

	/**
	 * The items stored in the registry
	 */
	private $vars = array();

	/**
	 * Sets a value to be stored in the registry
	 *
	 * @param string $index The key for the storing an item in the registry
	 * @param mixed $value An value to store.
	 *
	 * @return void
	 */
	public function __set($index, $value) {
		$this->vars[$index] = $value;
	}

	/**
	 * Retrieves an item from the registry.
	 *
	 * @param string $index The key where the item is stored
	 *
	 * @return mixed The returned item
	 */
	public function __get($index) {
		if (isset($this->vars[$index])) {
			return $this->vars[$index];
		}
	}

}
