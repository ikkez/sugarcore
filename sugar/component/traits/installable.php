<?php

namespace Sugar\Component\Traits;


trait Installable {

	/**
	 * do something while being installed
	 * @param $config
	 * @return bool
	 */
	static function install($config) {
		// overload if necessary
		return true;
	}

	/**
	 * do something when the component is being uninstalled
	 * @param $config
	 * @return bool
	 */
	static function uninstall($config) {
		// overload if necessary
		return true;
	}
}