<?php

namespace Sugar;


trait Flow {

	protected $ports = [
		'in' => [
		],
		'out' => [
		]
	];

	abstract function flow();

	/**
	 * register new IN port
	 * @param $key
	 * @param array $config
	 */
	function in($key,$config=array()) {
//		$config=$this->fw->extend('CORE.components.'.$this->name,$config);
		$config=$config+[
				'datatype'=>FALSE,
				'required'=>FALSE,
				'cached'=>FALSE,
			];
		$this->ports['in'][$key] = $config;
	}

	/**
	 * register new OUT port
	 * @param $key
	 * @param array $config
	 */
	function out($key,$config=array()) {
		$config=$config+[
				'datatype'=>FALSE,
				'required'=>FALSE,
				'cached'=>FALSE,
			];
		$this->ports['out'][$key] = $config;
	}

	function on($key,$handler,$args=[]) {
//		list($port,$key) = explode('.',$key,2);
//		$this->event->on($this->fw->get('CORE.active_app.name').'.'.$this->name.'.'.$key,
		$this->ev->on($this->_name.'.'.$key,[$handler,$args]);
	}
}