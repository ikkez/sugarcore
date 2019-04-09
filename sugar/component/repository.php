<?php

namespace Sugar\Component;


class Repository {

	protected
		$f3,
		$registry;

	function __construct(Registry $registry) {
		/** @var \Base $f3 */
		$this->f3 = \Base::instance();
		$this->registry = $registry;
	}

	/**
	 * scan package dir for ini file and extract component settings
	 * @param $path
	 * @return bool|mixed
	 */
	function loadPackage($path) {
		// find the package.ini
		$pgk_file = 'package.ini';
		$path = $this->f3->get('CORE.repo_path').rtrim($path,'/').
			'{/,/*/,/**/*/,/**/**/*/}'.$pgk_file;
		$scan = glob($path, GLOB_BRACE);

		if (!empty($scan)) {
			$component_path = $scan[0];
			// relative home dir of the package
			$home = substr($component_path,0,-strlen($pgk_file));

			// clean-up and load config
			$this->f3->clear('COMPONENT');
			$this->f3->config($component_path);

			// extend with default settings
			foreach (array_keys($this->f3->get('COMPONENT')) as $key) {
				$this->f3->extend('COMPONENT.'.$key,
					['name'=>$key]+$this->f3->get('CORE.component.defaults'),TRUE);
				$this->f3->set('COMPONENT.'.$key.'.home',$home);
			}
			// clean-up
			$components = $this->f3->get('COMPONENT');
			$this->f3->clear('COMPONENT');
			return $components;
		}
		else
			return FALSE;
	}

	/**
	 * find all packages within the repo path
	 */
	function findPackages() {
		$pgk_file = 'package.ini';
		$repo_path = $this->f3->get('CORE.repo_path');
		$root = strlen($this->f3->get('ROOT').$this->f3->get('BASE').$repo_path)+1;
		$end = strlen($pgk_file);
		$path = realpath($repo_path);
		$packages=[];
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $filePath) {
			if (is_int(strpos($filePath,$pgk_file)))
				$packages[] = substr($filePath,$root,-$end);
		}
		return $packages;
	}

	/**
	 * load a package and install all its components
	 * @param $path
	 */
	function installPackage($path) {
		$components = $this->loadPackage($path);
		if ($components) {
			$this->addComponents($components);

			foreach ($components as $key => $comp) {
				$config = $this->registry->load($key);
				if ($config && method_exists($comp['class'],'install'))
					call_user_func([$comp['class'],'install'],$config);
			}
		}
	}

	/**
	 * add a component to the registry
	 * @param $name
	 * @param $settings
	 */
	function addComponent($name, $settings) {
		if (!$this->registry->exists($name))
			$this->registry->save($name,$settings);
//		else
//			$this->f3->error(500,sprintf('The component `%s` already exist.',$name));
	}

	/**
	 * add multiple components to the registry
	 * @param $data
	 */
	function addComponents($data) {
		foreach ($data as $key => $settings)
			$this->addComponent($key,$settings);
	}

	/**
	 * remove component from registry
	 * @param $name
	 */
	function removeComponent($name) {
		if (!$this->registry->exists($name))
			trigger_error(sprintf('The component `%s` does not exist',$name));
		$this->registry->remove($name);
	}

}