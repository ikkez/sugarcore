<?php

namespace Component\Template;

use Sugar\Component;
use Sugar\View\FileViewInterface;

class Template extends Component implements FileViewInterface {

	/** @var FileViewInterface */
	protected $view;

	/** @var string */
	protected $template;

	/** @var string */
	protected $baseURL;

	function __construct(\Sugar\View\Template $view) {
		parent::__construct();
		$this->view = $view;
	}

	/**
	 * set template path
	 * @param $fileName
	 */
	function setTemplate($fileName) {
		$this->template = $fileName;
	}

	/**
	 * get template file path
	 */
	function getTemplate() {
		return $this->template;
	}

	/**
	 * set hive data
	 * @param $data
	 */
	function setDataArr($data) {
		foreach ($data as $key=>$val)
			$this->view->set($key,$val);
	}

	/**
	 * set single data key
	 * @param $key
	 * @param $value
	 */
	function setData($key,$value) {
		$this->view->set($key,$value);
	}

	function init() {
		// set app specific UI path
		if ($this->config['set_app_ui'] && !$this->fw->devoid('APP.UI'))
			$this->fw->concat('UI', ','.
				$this->fw->get('CORE.active_app.path').$this->fw->get('APP.UI'));

		$this->view->engine->filter('alias','\Sugar\Utility\UI::alias');
		$this->view->engine->filter('date',function($val) {
			return \Base::instance()->format('{0,date}',strtotime($val));
		});
		\Template\Tags\Form::initAll($this->view->engine);
		\Template\Tags\Image::init('image',$this->view->engine,[
			'temp_dir' => 'ui/compressed/img/'
		]);
	}

	/**
	 * return rendered template
	 * @return mixed
	 */
	function render() {

		// compute base path
		if ($this->baseURL)
			$this->view->baseURL=$this->baseURL;
		else
			$this->view->baseURL = $this->fw->SCHEME.'://'.$this->fw->HOST.
				($this->fw->PORT && !in_array($this->fw->PORT,[80,443])?(':'.$this->fw->PORT):'')
				.$this->fw->BASE.'/';

		if ($this->template)
			$this->view->setTemplate($this->template);

		$this->emit('beforerender',[$this]);

		return $this->view->render();
	}

	/**
	 * returns the rendered view as string
	 * @return string
	 */
	function dump() {
		echo $this->render();
	}

	/**
	 * set data key
	 * @return array
	 */
	public function get($key) {
		return $this->view->get($key);
	}

	/**
	 * get data key
	 * @param $key
	 * @param $val
	 */
	public function set($key,$val) {
		$this->view->set($key,$val);
	}
}