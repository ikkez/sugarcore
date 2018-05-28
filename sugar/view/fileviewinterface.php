<?php

namespace Sugar\View;


Interface FileViewInterface {

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
	 * get template file path
	 */
	function getTemplate();

	/**
	 * set template file path
	 * @param $filePath
	 */
	function setTemplate($filePath);

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