<?php

namespace Sugar;

use Sugar\Component\Registry;

class Component {

	/** @var string */
	protected $_name;

	/** @var Component */
	protected $_parent;

	/** @var \Base */
	protected $fw;

	/** @var \Event */
	protected $ev;

	/** @var Registry */
	protected $registry;

	/** @var array */
	protected $config = array();

	/** @var array */
	protected $settings = array();

	function __construct() {
		$this->fw = \Base::instance();
		$this->ev = \Event::instance();
		$this->registry = Registry::instance();
	}

	/**
	 * init method, overwrite if needed
	 */
	function init() {}

	/**
	 * apply component configuration
	 * @param null $instanceName
	 * @param array $config
	 * @param null $parent
	 */
	public function config($instanceName=NULL, $config=[],$parent=NULL) {
		$this->_name = $instanceName ?: get_called_class();
		if ($parent)
			$this->_parent=$parent;

		if (empty($config)) {
			$config = $this->registry->load($instanceName);
		}
		if ($config) {
			if (isset($config['config'])) {
				$this->config = array_replace_recursive($this->config,$config['config']);
				// auto-conf class properties
				foreach ($this->config as $key=>$val) {
					if (property_exists($this,$key))
						$this->{$key}=$val;
				}
				unset($config['config']);
			}
			$this->settings = array_replace_recursive($this->settings,$config);
		}

		$this->init();

		if (!empty($this->settings['components'])) {
			foreach ($this->settings['components'] as &$component) {
				/** @var Component $component */
				$component = $this->registry->create($component,null,$this);
				unset($component);
			}
		}

	}

	/**
	 * send and event
	 * @param $key
	 * @param null $args
	 * @param array $context
	 * @param bool $hold
	 * @param bool $scoped
	 * @return mixed
	 */
	function emit($key,$args=null,&$context=[],$hold=true,$scoped=true) {
		if ($scoped)
			$key=get_called_class().'.'.$key;
		return $this->ev->emit($key,$args,$context,$hold);
	}

	/**
	 * fetch an event by a parent component
	 * @param $key
	 * @param $func
	 * @param int $priority
	 * @param array $options
	 * @return bool
	 */
	function onParent($key,$func,$priority=10,$options=array()) {
		if (!$this->_parent)
			return false;
		$key=$this->_parent->settings['class'].'.'.$key;
		$this->ev->on($key,$func,$priority,$options);
	}

}