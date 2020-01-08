<?php

namespace Sugar\Storage\Simple;


use DB\Jig\Mapper;
use Sugar\Storage\KeyValueInterface;

class Jig implements KeyValueInterface {

	protected $mapper;

	protected $_key;

	protected $data = [];

	function __construct(\DB\Jig $db, $fileName,$key='id') {
		$this->mapper = new Mapper($db,$fileName);
		$this->_key = $key;
		foreach ($db->read($fileName) as $item) {
			$this->data[$item[$key]] = $item;
		}
	}

	function load($key) {
		return (array_key_exists($key,$this->data))
			? $this->data[$key] : false;
	}

	function save($key,$val) {
		$this->mapper->reset();
		$this->mapper->load(['@'.$this->_key.' = ?', $key]);
		$this->mapper->copyfrom($val);
		$this->mapper->save();
		$this->data[$key] = $this->mapper->cast();
	}

	function delete($key) {
		$this->mapper->reset();
		$this->mapper->load(['@'.$this->_key.' = ?',$key]);
		if ($this->mapper->valid()) {
			$this->mapper->erase();
			unset($this->data[$key]);
		}
	}

	function getAll() {
		return $this->data;
	}
}