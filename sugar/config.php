<?php
/**
	Jig-based Config Wrapper

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2013-2018 ~ ikkez
	Christian Knuth <ikkez0n3@gmail.com>

		@version 0.8.0
		@date: 23.03.2018
		@since: 29.10.2013
 **/

namespace Sugar;

class Config extends \DB\Jig\Mapper {

	protected $key;

	public function __construct($table='config.json',$key='CONFIG') {
		$this->key=$key;
		$db = new \DB\Jig(\Base::instance()->get('CORE.data_path'));
		parent::__construct($db,$table);
		$this->load();
	}

	/**
	 * copy to hive key
	 */
	public function expose() {
		$this->copyto($this->key);
	}

	/**
	 * persist from hive key
	 */
	public function persist() {
		$this->save();
	}

	public function copyfromHive() {
		$this->copyfrom($this->key);
	}

	static public function instance() {
		if (\Registry::exists($class=get_called_class()))
			$cfg = \Registry::get($class);
		else {
			$cfg = new self;
			\Registry::set($class,$cfg);
		}
		return $cfg;
	}

}