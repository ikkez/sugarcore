<?php

namespace Sugar\View;


Interface TemplateInterface extends ViewInterface {

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
	 * return used engine instance
	 * @return mixed
	 */
	public function engine();

}