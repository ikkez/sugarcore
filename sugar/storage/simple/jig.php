<?php

namespace Sugar\Storage\Simple;


use DB\Jig\Mapper;
use Sugar\Storage\KeyValueInterface;

class Jig implements KeyValueInterface {

	protected $mapper;

	protected $_key;

	function __construct(\DB\Jig $db, $fileName,$key='id') {
		$this->mapper = new Mapper($db,$fileName);
		$this->_key = $key;
	}

	function load($key) {
		$data = $this->mapper->load(['@'.$this->_key.' = ?',$key]);
		return $data? $data->cast() : false;
	}

	function save($key,$val) {
		$this->mapper->reset();
		$this->mapper->load(['@'.$this->_key.' = ?', $key]);
		$this->mapper->copyfrom($val);
		$this->mapper->save();
	}

	function delete($key) {
		$this->mapper->reset();
		$this->mapper->load(['@'.$this->_key.' = ?',$key]);
		if ($this->mapper->valid())
			$this->mapper->erase();
	}

	function getAll() {
		$all = $this->mapper->find();
		$out=[];
		foreach ($all?:[] as $record)
			$out[$record->{$this->_key}]=$record->cast();
		return $out;
	}
}