<?php


namespace Sugar\View;


class Template extends Base implements TemplateInterface {

	protected $filePath = 'layout.html';

	/**
	 * @var \Template
	 */
	public $engine;

	public $rnd;

	/**
	 * Template constructor.
	 */
	function __construct() {
		$this->engine = new \Template();
		$this->rnd = rand(0,100);

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
		return $this->engine->render($this->filePath,$mime,$this->data);
	}

	/**
	 * render the view and send to client
	 */
	function dump() {
		echo $this->render('text/html');
	}
}