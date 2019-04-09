<?php

namespace Sugar\View;


Interface ViewInterface {

	/**
	 * render the view and send to client
	 * @return string
	 */
	function dump();

	/**
	 * returns the rendered view as string
	 */
	function render();

	/**
	 * set data key
	 * @param $key
	 * @return array
	 */
	public function get($key);

	/**
	 * check if a data key exists
	 * @return bool
	 */
	public function exists($key);

	/**
	 * get data key
	 * @param $key
	 * @param $val
	 */
	public function set($key,$val);
}