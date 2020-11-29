<?php

namespace Sugar;

use Sugar\Service\Registry;

trait ComponentTrait {

	/** @var string */
	protected $_name = __CLASS__;

	/** @var Component */
	protected $_parent;

	/** @var \Base */
	protected $fw;

	/** @var \Sugar\Event */
	protected $ev;

	/** @var \Sugar\Event */
	protected $ev_local;

	/** @var Registry */
	protected $registry;

	/** @var array */
	protected $config = [];

	/** @var array */
	protected $settings = [];

	/**
	 * apply component configuration
	 * @param null $instanceName
	 * @param array $config
	 * @param null $parent
	 */
	public function config($instanceName=NULL, $config=[],$parent=NULL) {

		if (!$this->fw)
			$this->fw = \Base::instance();
		if (!$this->ev)
			$this->ev = \Sugar\Event::instance();
		if (!$this->registry)
			$this->registry = Registry::instance();

		if ($instanceName)
			$this->_name = $instanceName;

		if ($parent)
			$this->_parent=$parent;

		if (empty($config))
			$config = $this->registry->load($instanceName);


		if ($config) {
			if (isset($config['config'])) {
				$this->config = array_replace_recursive($this->config,$config['config']);
				// auto-conf class properties
				foreach ($this->config as $key=>$val) {
					// use setter function if existing
					if (method_exists($this,$setter='set'.ucfirst($this->fw->camelcase($key)))) {
						$this->{$setter}($val);
					}
					elseif (property_exists($this,$key)) {
						// patch existing array values
						if (is_array($this->{$key}) && is_array($val))
							$this->{$key} = array_replace_recursive($this->{$key},$val);
						else
							$this->{$key}=$val;
					}
				}
			}
			unset($config['config']);
			$this->settings = array_replace_recursive($this->settings,$config);
		}

		$this->broadcast('component_load',['name'=>$this->_name,'config'=>$this->config],$this);

		if (!empty($this->settings['components'])) {
			foreach ($this->settings['components'] as $key=>&$component) {
				/** @var Component $component */
				if (isset($this->settings['extend'][$key]))
					$extConf = $this->settings['extend'][$key];
				else
					$extConf = null;
				$cObj = $this->registry->create($component,$extConf, $this);
				// map keyed component to class property
				if (is_string($key) && property_exists($this,$key))
					$this->{$key} = $cObj;
				$component = $cObj;
				unset($component);
			}
		}

		// port are nothing more than local events
		if (!empty($this->settings['port'])) {
			foreach ($this->settings['port'] as $key => $call_stack) {
				if (!is_array($call_stack))
					$call_stack=[$call_stack];
				// port can have multiple listeners
				foreach ($call_stack as $pkey => $call) {
					if (is_int(strpos($call,'.')))
						// if the call goes to a component instance, we create/resolve the object first
						$this->on($key,function($args,$context=NULL,$ev=NULL) use ($call){
							$call = explode('.',$call);
							/** @var Component $component */
							$component = $this->registry->create($call[0],null,$this);
							$this->fw->call([$component,$call[1]],[$args,&$context,$ev]);
						});
					else
						// simple function / F3 callable string
						$this->on($key,$call);
				}
			}
		}

		if (method_exists($this,'init'))
			$this->init();

		if (!empty($this->settings['components']))
			foreach ($this->settings['components'] as $key=>$component) {
				if (method_exists($component,'ready'))
					$component->ready();
			}

		if (!$parent && method_exists($this,'ready'))
			$this->ready();

		$this->broadcast('component_ready',['name'=>$this->_name,'config'=>$this->config,'settings'=>$this->settings],$this);

	}

	/**
	 * get component settings
	 * @return array
	 */
	function getSettings() {
		return $this->settings;
	}

	/**
	 * send an event
	 * @param $key
	 * @param null $args
	 * @param array $context
	 * @param bool $hold
	 * @return mixed
	 */
	function broadcast($key,$args=null,&$context=[],$hold=true) {
		return $this->ev->emit($key,$args,$context,$hold);
	}

	/**
	 * send an event only locally
	 * @param $key
	 * @param null $args
	 * @param array $context
	 * @param bool $hold
	 * @param bool $scoped
	 * @return mixed
	 */
	function emit($key,$args=null,&$context=[],$hold=true) {
		if (!$this->ev_local)
			$this->ev_local = $this->ev->watch($this);
		if ($track = $this->ev_local->has($key)) {
			$this->broadcast('component_port_open',['name'=>$this->_name,'config'=>$this->config,'port'=>$key],$this);
		}
		$out = $this->ev_local->emit($key,$args,$context,$hold);
		if ($track)
			$this->broadcast('component_port_close',['name'=>$this->_name,'config'=>$this->config,'port'=>$key],$this);
		return $out;
	}

	/**
	 * listen on local event
	 * @param $key
	 * @param $func
	 * @param int $prio
	 * @param array $options
	 */
	function on($key,$func,$prio=10,array $options=[]) {
		if (!$this->ev_local)
			$this->ev_local = $this->ev->watch($this);
		return $this->ev_local->on($key,$func,$prio,$options);
	}

	/**
	 * compute and return the current relative component path
	 * @return string
	 */
	function getComponentPath() {
		$basePath = new \ReflectionObject($this);
		$root = $this->fw->fixslashes($this->fw->ROOT.$this->fw->BASE);
		if (strlen($root) > getcwd()) {
			// TODO: need public path
			$root=getcwd();
		}
		return trim(str_replace($root,'',
				$this->fw->fixslashes(dirname($basePath->getFileName()))),'/').'/';
	}
}