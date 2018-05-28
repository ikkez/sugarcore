<?php

namespace Sugar\View;

abstract class Base extends \Magic implements ViewInterface {
	
	protected $data = [];

	/**
	 * set data key
	 * @return array
	 */
	public function &get($key) {
		return $this->data[$key];
	}

	/**
	 * get data key
	 * @param $key
	 * @param $val
	 */
	public function set($key,$val) {
		$this->data[$key] = $val;
	}

	/**
	 * Return TRUE if key is not empty
	 * @return bool
	 * @param $key string
	 **/
	function exists($key) {
		return array_key_exists($key,$this->data);
	}

	/**
	 * Unset key
	 * @param $key string
	 **/
	function clear($key) {
		unset($this->data[$key]);
	}

	/**
	 * set all data
	 * @param $data
	 */
	function setData($data) {
		$this->data = $data;
	}

	/**
	 * get all data
	 * @return array
	 */
	function getData() {
		return $this->data;
	}

	/**
	 * returns the rendered view as string
	 * @return string
	 */
	abstract function render();

	/**
	 * render the view and send to client
	 */
	function dump() {
		echo $this->render();
	}
	
}