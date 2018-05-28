<?php

namespace Sugar\Storage\Simple;


use Sugar\Storage\KeyValueInterface;

class Hive implements KeyValueInterface {

	protected $data;

	function __construct($hiveKey) {
		$this->data = \Base::instance()->get($hiveKey);
	}

	function getOne($val) {
		return (array_key_exists($val,$this->data))
			? $this->data[$val] : false;
	}

	function saveOne($data,$val) {
		$this->data[$val]=$data;
	}

	function getAll() {
		return $this->data;
	}
}