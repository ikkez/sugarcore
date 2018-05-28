<?php

namespace Sugar\Component;

use Dice\Dice;
use Sugar\Component;

class Registry extends \Prefab {

	const ERROR_UnitNotInstalled = 'Component or Class of type "%s" not found';

	protected
		$cached_configs = [];

	protected
		$resource;

	function __construct(\Sugar\Storage\KeyValueInterface $model) {
		$this->resource = $model;
	}

	/**
	 * load component configuration
	 * @param $name
	 * @return bool|mixed
	 */
	public function load($name) {
		if (array_key_exists($name,$this->cached_configs))
			return $this->cached_configs[$name];

		if ($config = $this->resource->getOne($name)) {

			// load parent config, if this is an instance of another component
			if (isset($config['instance'])) {
				$config = array_replace_recursive(
					$this->load($config['instance']),$config);
			}

			$this->cached_configs[$name] = $config;

			// configure Dependency Injections
			if (isset($config['dic'])) {
				/** @var Dice $dice */
				$dice = \Registry::get('DICE');
				$subs=[];
				foreach ($config['dic'] as $key => $args) {
//					if ($args[1][0] == ':') {
//						$conf=$this->load(ltrim($args[1],':'));
//						if ($conf)
//							$args[1]=$conf['class'];
//					}
					$subs[$args[0]] = $args[1];
				}

//				if (isset($config['instance']))
					$dice->addRule('$'.$name,[
						'instanceOf'=>$config['class'],
						'substitutions'=>$subs,
	//					'call' => [
	//						['config', [$name]], // buggy as well
	//					]
					]);
//				else
				// have to register twice, possibly bug in DICE?
//					$dice->addRule($config['class'],[
//						'instanceOf'=>$config['class'],
//						'substitutions'=>$subs,
//						'call' => [
//							['config', [$name]], // buggy as well
//						]
//					]);
			}

			return $config;
		} else return false;
	}

	function preload() {
		// TODO: cache this later
		foreach ($this->resource->getAll() as $key=>$item) {
			$this->load(isset($item['name'])?$item['name']:$key);
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
		// load instance configuration
		$config = $conf ?: $this->load($name);

		if ($config) {

			$classType = $config['class'];

			if (method_exists($classType,'getInstance'))
				return $classType::getInstance($config);

			if (isset($config['dic']))
				$classType='$'.$name;

			/** @var Component $obj */
			$obj = \Registry::get('DICE')->create($classType);
			$obj->config($name,$config,$parent);
			return $obj;
		} else
			// if component is not defined, search for matching class
			if (class_exists($name)) {
				if (method_exists($name,'getInstance'))
					return $name::getInstance();
				else
					// create component with dependency injection
					return \Registry::get('DICE')->create($name);
			}
			else
				trigger_error(sprintf(self::ERROR_UnitNotInstalled,$name),E_USER_ERROR);
		return false;
	}

	/**
	 * @param $name
	 * @param null $instance
	 * @param string $class
	 * @return mixed
	 */
	public function saveConfig($name,$instance=NULL,$class='Sugar\Component') {
		return $this->resource->saveOne([
			'name' => $name,
			'class' => $class,
			'instance' => $instance,
		],$name,'name');
	}
}