<?php

namespace Sugar\Component\Template;

use Sugar\Component;
use Sugar\Utility\UI;
use Sugar\View\TemplateInterface;

class Template extends Component implements TemplateInterface {

	/** @var TemplateInterface */
	protected $view;

	/** @var string */
	protected $template;

	/** @var string */
	public $baseURL;

	/**
	 * Template constructor.
	 * @param TemplateInterface $view
	 */
	function __construct(TemplateInterface $view) {
		$this->view = $view;

		// forward engine events to event bus system
		$engine = $this->view->engine();
		if ($engine instanceof \Preview) {
			$engine->afterrender(function($data,$fileName){
				$this->emit('beforerender',[
					'obj'=>$this,
					'fileName'=>$fileName
				],$data);
				return $data;
			});
		}
		if ($engine instanceof \View) {
			$engine->afterrender(function($data,$fileName){
				$this->emit('afterrender',[
					'obj'=>$this,
					'fileName'=>$fileName
				],$data);
				return $data;
			});
		}
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
	 * set single data key
	 * @param $key
	 * @param $value
	 */
	function setData($key,$value) {
		$this->view->set($key,$value);
	}

	function init() {
		// set app specific UI path
		if ($this->config['set_app_ui'] && !$this->fw->devoid('APP.UI',$ui)) {
			if (!is_array($ui))
				$ui=[$ui];
			foreach ($ui as &$ui_path) {
				$ui_path=$this->fw->get('APP.PATH').$ui_path;
				unset($ui_path);
			}
			$ext_ui = $this->fw->get('UI');
			$this->fw->set('UI', implode(';',$ui).';'.$ext_ui);
		}

		if (($engine = $this->view->engine()) instanceof \Preview) {
			$engine->filter('alias','\Sugar\Utility\UI::instance()->alias');
			$engine->filter('date',function($val) {
				return \Base::instance()->format('{0,date}',strtotime($val));
			});
			// TODO: remove that, it's included in latest F3 core dev
			$engine->filter('nl2br',function($val) {
				return nl2br($val);
			});
			$engine->filter('clean',function($val,$args=NULL) {
				return $this->fw->clean($val,$args);
			});
			$engine->filter('ui',function($val,$args=NULL) {
				return UI::instance()->uiPath($val);
			});
		}

		if (($engine = $this->view->engine()) instanceof \Template) {
			\Template\Tags\Form::initAll($engine);
			\Template\Tags\Image::init('image',$engine,[
				'temp_dir' => 'ui/compressed/img/',
				'not_found_callback' => function($filePath) {
					$this->broadcast('log.warning',['msg'=>'File not found: "'.$filePath.'"'],$this);
				}
			]);
//			\Template\Tags\Image::init('img',$engine,[
//				'temp_dir' => 'ui/compressed/img/',
//				'not_found_callback' => function($filePath) {
//					$this->broadcast('log.warning',['msg'=>'File not found: "'.$filePath.'"'],$this);
//				}
//			]);
			$engine->extend('pagebrowser','\Pagination::renderTag');
		}

		// compute base path
		if (!$this->baseURL) {
			$this->baseURL = $this->fw->SCHEME.'://'.$this->fw->HOST.
				($this->fw->PORT && !in_array($this->fw->PORT,[80,443])?(':'.$this->fw->PORT):'')
				.$this->fw->BASE.'/';
			// append when app is within sub-path
//			$append = $this->fw->get('APP.ROUTE');
//			$this->baseURL.= $append?$append.'/':'';
		}
	}

	/**
	 * return rendered template
	 * @return mixed
	 */
	function render() {

		$this->view->baseURL=$this->baseURL;

		if ($this->template)
			$this->view->setTemplate($this->template);

		$this->emit('render',[$this]);

		return $this->view->render();
	}

	/**
	 * send the rendered view to client
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

	/**
	 * check if a data key exists
	 * @param $key
	 * @return bool
	 */
	public function exists($key) {
		return $this->view->exists($key);
	}

	/**
	 * return used engine instance
	 * @return mixed
	 */
	public function engine() {
		return $this->view->engine();
	}

	function __call($name,$arguments) {
		return $this->view->{$name}(...$arguments);
	}
}