<?php


namespace Sugar\View;


class JSON extends Base {
	
	/**
	 * render the view
	 * @return mixed
	 */
	function render() {
		return json_encode($this->data);
	}

	/**
	 * add headers when sending to client
	 */
	function dump() {
		header('Content-Type: application/json');
		parent::dump();
	}
}