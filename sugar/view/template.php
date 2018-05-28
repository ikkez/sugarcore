<?php


namespace Sugar\View;


class Template extends Base implements FileViewInterface {

	protected $filePath = 'layout.html';

	/**
	 * @var \Template
	 */
	public $engine;

	/**
	 * Template constructor.
	 */
	function __construct() {
		$this->engine = \Template::instance();
	}

	/**
	 * @return string
	 */
	public function getTemplate() {
		return $this->filePath;
	}

	/**
	 * @param string $filePath
	 */
	public function setTemplate($filePath) {
		$this->filePath = $filePath;
	}

	/**
	 * render the view
	 * @param string $mime
	 * @return mixed
	 */
	function render($mime='text/html') {
		if (!empty($this->data))
			\Base::instance()->mset($this->data);
		return $this->engine->render($this->filePath,$mime);
	}
}