<?php


namespace Sugar\View;


class Preview extends Base implements TemplateInterface {

	protected $filePath = 'layout.html';

	/**
	 * @var \Preview
	 */
	public $engine;

	/**
	 * Template constructor.
	 */
	function __construct() {
		$this->engine = new \Preview();
	}

	/**
	 * return used engine instance
	 * @return mixed
	 */
	public function engine() {
		return $this->engine;
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
	function render($mime=NULL) {
		if (!empty($this->data))
			\Base::instance()->mset($this->data);
		return $this->engine->render($this->filePath,$mime);
	}

	/**
	 * render the view and send to client
	 */
	function dump() {
		echo $this->render('text/html');
	}
}