<?php

namespace Sugar\Service;

use Dice\Dice;
use Sugar\Component;

class Factory extends \Prefab {

	protected $registry;
	protected $fw;

	function __construct() {
		$this->registry = Registry::instance();
		$this->fw = \Base::instance();
	}

	/**
	 * instantiate a class or component and call a method on it
	 * i.e. "foo.bar"
	 * @param $func
	 * @param null $args
	 * @param string $hooks
	 * @return FALSE|mixed
	 */
	function call($func,$args=NULL,$hooks='') {
		if ($args && !is_array($args))
			$args=[$args];
		if (preg_match('/(.+)\h*(->|\.|::)\h*(.+)/s',$func,$parts)) {
			// detect and reroute component call
			if ($parts[2]=='.') {
				$component = $this->registry->create($parts[1]);
				$func = [$component,$parts[3]];
			}
		}
		return $this->fw->call($func,$args,$hooks);
	}
}