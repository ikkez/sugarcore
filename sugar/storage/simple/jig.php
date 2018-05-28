<?php

namespace Sugar\Storage\Simple;


use DB\Jig\Mapper;
use Sugar\Storage\KeyValueInterface;

class Jig implements KeyValueInterface {

	protected $mapper;

	protected $_key;

	function __construct(\DB\Jig $db, $fileName,$key='id') {
		$this->mapper = new Mapper($db,'');
		$this->_key = $key;
	}

	function getOne($val) {
		$data = $this->mapper->load(['@'.$this->_key.' = ?',$val]);
		return $data? $data->cast() : false;
	}

	function saveOne($data,$val) {
		$this->mapper->reset();
		$this->mapper->load(['@'.$this->_key.' = ?',$val]);
		$this->mapper->copyfrom($val);
		$this->mapper->save();
	}

	function getAll() {
		$all = $this->mapper->find();
		$out=[];
		foreach ($all as $record)
			$out[]=$record->cast();
		return $out;
	}
}