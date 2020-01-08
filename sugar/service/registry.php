<?php

namespace Sugar\Service;

use Dice\Dice;
use Sugar\Component;

class Registry extends \Prefab {

	const ERROR_UnitNotInstalled = 'Component or Class of type "%s" not found in Registry';

	protected
		$cached_configs = [],
		$dic_rule_hash_pool = [];

	/** @var \Sugar\Storage\KeyValueInterface $resource */
	protected $resource;

	/** @var \Sugar\Storage\KeyValueInterface $resource */
	protected $fallback_resource;

	/** @var Dice $dice */
	protected $DICE;

	/**
	 * Registry constructor.
	 * @param \Sugar\Storage\KeyValueInterface $model
	 */
	function __construct(\Sugar\Storage\KeyValueInterface $model) {
		$this->resource = $model;
		$this->DICE = \Registry::get('DICE');
	}

	/**
	 * load component configuration
	 * @param $name
	 * @param null $extConf
	 * @return bool|mixed
	 */
	public function load($name,$extConf=NULL) {

		if (array_key_exists($name,$this->cached_configs))
			$config = $this->cached_configs[$name];

		elseif ($config = $this->exists($name)) {
			// load parent config, if this is an instance of another component
			if (isset($config['instance'])) {
				$inheritConfigOnly = !empty($config['class']);
				$config = array_replace_recursive(
					$this->load($config['instance']),$config);
				if ($inheritConfigOnly)
					unset($config['instance']);
			}
			$this->cached_configs[$name] = $config;
		} else {
			// cache non existing, to reduce resource querying
			$this->cached_configs[$name] = $config;
		}

		// extend config with overwrites
		if ($extConf)
			$config = array_replace_recursive($config?:[],$extConf);

		return $config;

	}

	/**
	 * set Dependency Injection Container rules
	 * @param string $name name of component or class identifier
	 * @param array $config
	 * @param Component|null $parent
	 */
	function setDicRules($name,$config,Component $parent=null) {
		// configure Dependency Injections
		$rules = [
			'call' => [
				// when components are created by DI,
				// we need to make sure config() is called automatically
				['config', [$name,[],$parent]],
			]
		];

		if (isset($config['dic'])) {
			$subs=[];
			foreach ($config['dic'] as $key => $args) {
				if (!is_array($args))
					continue;
//					\Base::instance()->error(500,sprintf('Dependency "%s" must be fulfilled for component "%s"',$args,$name));
				list($interface,$adapter) = $args;
				$sub = ['instance'=>$adapter];
				if ($adapter[0] == '$') {
					// lazy-load nested configuration
					// this will add the required DICE rules for later substitutions
					$c_name = ltrim($adapter,'$');
					$c_conf = $this->load($c_name);
					$this->setDicRules($c_name,$c_conf,$parent);

					if (!isset($rules['shareInstances']))
						$rules['shareInstances'] = [];

					$rules['shareInstances'][]=$adapter;

				} elseif ($parent && $adapter[0] == '<') {
					// link to shared dependency on a parent object
					// this is mainly used for sub-components
					$key = trim($adapter,'< ');
					if (property_exists($parent,$key)) {

						$ref_o=new \ReflectionObject($parent);
						$ref=$ref_o->getProperty($key);
						$visible=$ref->ispublic();
						if (!$visible) {
							$ref->setAccessible(true);
							$sub=$ref->getValue($parent);
							$ref->setAccessible(false);
						} else {
							$sub = $parent->{$key};
						}
						unset($ref,$ref_o);
					} else {
						$ps = $parent->getSettings();
						if (isset($ps['components'][$key])) {
							$sub = $ps['components'][$key];
						}
					}
				} else {
					// if adapter is a regular class, mark it as shared for sub-components
					if (!isset($rules['shareInstances']))
						$rules['shareInstances'] = [];
					$rules['shareInstances'][]=$adapter;
				}
				$subs[$interface] = $sub;
			}

			// add the rules for "named" instances
			$rules['substitutions']=$subs;
		}

		// configured component
		if ($config) {
			if (!isset($config['class']))
				\Base::instance()->error(500,'Component not existing: '.$name);

			$this->DICE->addRule('$'.$name,$rules+['instanceOf'=>$config['class']]);

			// add the raw class rule here, but only if it's not an instance
			// of another component, since the parent component already has this
			// class-based rule, so we don't overwrite it here
//			if (!isset($config['instance'])) {
				$this->DICE->addRule($config['class'],$rules);
//			}
		} else {
			// simple class, register as named for using as shared dependency
			$this->DICE->addRule('$'.$name,['instanceOf'=>$name]);
		}
	}

	/**
	 * check if a component exists, if so its config is returned
	 * @param $name
	 * @return mixed
	 */
	function exists($name) {
		$out = $this->resource->load($name);
		if ($this->fallback_resource) {
			if ($out) {
				$out_ext = $this->fallback_resource->load($name);
				if (is_array($out_ext))
					$out = array_replace_recursive($out,$out_ext);
			} else {
				$out = $this->fallback_resource->load($name);
			}
		}
		return $out;
	}

	/**
	 * load all existing configurations
	 */
	function loadAll() {
		foreach ($this->resource->getAll() as $key=>$item) {
			$this->load(isset($item['name'])?$item['name']:$key);
		}
	}

	/**
	 * map classes to components and preload those component DIC rules
	 * this is mainly needed for routing to components via F3 Router
	 * @param array $rules key-value pairs [class => component-name]
	 */
	function mapClassesToComponents($rules) {
		foreach ($rules as $class=>$name) {

			if ($config = $this->exists($name)) {
				// load parent config, if this is an instance of another component
				if (isset($config['instance'])) {
					$config = array_replace_recursive(
						$this->load($config['instance']),$config);
				}

				$config['class'] = $class;
				$this->cached_configs[$class] = $config;

				$this->setDicRules($class,$config);
			}
		}
	}

	/**
	 * add additional custom Dependency Injection Container rules
	 * @param $rules
	 */
	function addDicRules($rules) {
		foreach ($rules as $key => $args) {
			$this->DICE->addRule($key,$args);
		}
	}

	/**
	 * create Component instance
	 * @param $name
	 * @param null $conf
	 * @param null $parent
	 * @return mixed
	 */
	public function create($name,$conf=null,$parent=NULL) {

		// get instance configuration
		$config = $this->load($name,$conf);

		if ($config) {
			// receive app instance if already existing
			if (isset($config['type']) && $config['type']=='app'
				&& \Registry::exists('$'.$config['class'])) {
				return \Registry::get('$'.$config['class']);
			}
			// receive alias / singleton object
			if (isset($config['alias']) && $config['alias'] == $name
				&& \Registry::exists('$'.$name)) {
				return \Registry::get('$'.$name);
			}
		}

		$this->setDicRules($name,$config,$parent);

		if ($config) {

			$classType = $config['class'];
			unset($config['meta']);

			if (method_exists($classType,'getInstance'))
				return $classType::getInstance($config);

			if (isset($config['dic']))
				$classType='$'.$name;

			/** @var Component $obj */
			$obj = $this->DICE->create($classType);

			if (isset($config['alias'])) {
				// save alias for singleton usage
				\Registry::set('$'.$config['alias'],$obj);
				$this->cached_configs[$config['alias']]=$config;
			}
			return $obj;

		} else
			// FALLBACK: if component is not defined, search for matching class
			if (class_exists($name)) {
				if (method_exists($name,'getInstance'))
					return $name::getInstance();
				else
					// create component with dependency injection
					return $this->DICE->create($name);
			}
			else
				\Base::instance()->error(500, sprintf(self::ERROR_UnitNotInstalled,$name));
		return false;
	}


	/**
	 * save a component configuration to the registry
	 * @param $name
	 * @param array $settings
	 * @return mixed
	 */
	public function save($name,$settings) {
		if (empty($settings['instance']) && empty($settings['class']))
			trigger_error('Please define at least an `instance` name or a `class` property.');
		return $this->resource->save($name, $settings);
	}

	/**
	 * remove a component configuration from the registry
	 * @param $name
	 * @return mixed
	 */
	public function remove($name) {
		return $this->resource->delete($name);
	}

	/**
	 * return component storage handler
	 * @return \Sugar\Storage\KeyValueInterface
	 */
	public function getModel() {
		return $this->resource;
	}

	/**
	 * @param $model
	 */
	public function enableHIVEComponents() {
		$this->fallback_resource = new \Sugar\Storage\Simple\Hive('COMPONENTS');
	}
}