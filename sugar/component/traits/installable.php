<?php

namespace Sugar\Component\Traits;


trait Installable {

	/**
	 * do something while being installed
	 * @param $data
	 * @return bool
	 */
	static function install($data) {
		// overload if necessary
		return true;
	}

	/**
	 * do something when the component is being uninstalled
	 * @param $data
	 * @return bool
	 */
	static function uninstall($data) {
		// overload if necessary
		return true;
	}
}