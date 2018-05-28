<?php

namespace Sugar\View;


Interface ViewInterface {

	/**
	 * returns the rendered view as string
	 * @return string
	 */
	function dump();

	/**
	 * render the view and send to client
	 */
	function render();

	/**
	 * set data key
	 * @return array
	 */
	public function get($key);

	/**
	 * get data key
	 * @param $key
	 * @param $val
	 */
	public function set($key,$val);
}