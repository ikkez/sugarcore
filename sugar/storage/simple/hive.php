<?php

namespace Sugar\Storage\Simple;


use Sugar\Storage\KeyValueInterface;

class Hive implements KeyValueInterface {

	protected $data;

	function __construct($hiveKey) {
		$this->data = &\Base::instance()->ref($hiveKey);
	}

	function load($key) {
		return (array_key_exists($key,$this->data))
			? $this->data[$key] : false;
	}

	function save($key,$val) {
		$this->data[$key]=$val;
	}

	function getAll() {
		return $this->data;
	}

	/**
	 * remove key from storage
	 * @param $key
	 * @return mixed
	 */
	function delete($key) {
		unset($this->data[$key]);
		// not possible to persist this data of course.
		return true;
	}
}