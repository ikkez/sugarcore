<?php

/**
 *  Sugarcore - Component Repository Manager
 *
 *  The contents of this file are subject to the terms of the GNU General
 *  Public License Version 3.0. You may not use this file except in
 *  compliance with the license. Any of the license terms and conditions
 *  can be waived if you get permission from the copyright holder.
 *
 *  Copyright (c) 2019
 *  https://github.com/ikkez/
 *
 *  @author   Christian Knuth <mail@ikkez.de>
 *
 */

namespace Sugar\Service;


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
//		$path = rtrim($path,'/').
//			'{/,/*/,/**/*/,/**/**/*/}'.$pgk_file;
		$path = rtrim($path,'/').'/'.$pgk_file;
		$scan = glob($path, GLOB_BRACE);

		if (!empty($scan)) {
			$component_path = $scan[0];
			// relative home dir of the package
			$home = substr($component_path,0,-strlen($pgk_file));

			// clean-up and load config
			$pkg=$this->f3->get('PACKAGE');
			$this->f3->set('PACKAGE',NULL);
			$this->f3->clear('COMPONENT');
			$this->f3->config($component_path);

			// extend with default settings
			if (!$this->f3->devoid('COMPONENT'))
				foreach (array_keys($this->f3->get('COMPONENT')) as $key) {
					if (!$this->f3->devoid('PACKAGE')) {
						$this->f3->extend('COMPONENT.'.$key,'PACKAGE',TRUE);
					}
					$this->f3->extend('COMPONENT.'.$key,
						['name'=>$key]+
						$this->f3->get('CORE.component.defaults'),TRUE);
					if ($this->f3->devoid('COMPONENT.'.$key.'.meta.title'))
						$this->f3->set('COMPONENT.'.$key.'.meta.title',$key);
					$this->f3->set('COMPONENT.'.$key.'.home',$home);
					$this->f3->set('COMPONENT.'.$key.'.home',$home);
				}
			// clean-up
			$components = $this->f3->get('COMPONENT');
			$this->f3->clear('COMPONENT');
			$this->f3->set('PACKAGE',$pkg);
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
		$out=[];
		if ($components) {
			$added = $this->addComponents($components,TRUE);
			foreach ($added as $key => $success)
				if ($success)
					$out[]=$key.' added';


			foreach ($components as $key => $comp) {
				$success=$this->installComponent($key);
				if ($success!==NULL) {
					if (is_array($success)) {
						foreach ($success as $line)
							$out[] = $line;
						$out[]=$key.' installed';
					}
					else
						$out[]=$key.' installed: '.((string)(bool)$success);
				}
			}
		}
		return $out;
	}

	/**
	 * add a component to the registry
	 * @param $name
	 * @param $settings
	 */
	function addComponent($name, $settings, $overwrite=FALSE) {
		if ($overwrite || !$this->registry->exists($name)) {
			$this->registry->save($name,$settings);
			$dep = Dependency::instance($this->registry);
			$dep->addComponentDependencies($name);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * run component installer
	 * @param $name
	 * @return mixed|null
	 */
	function installComponent($name) {
		$out=NULL;
		if ($this->registry->exists($name)) {
			$config = $this->registry->load($name);
			if ($config && method_exists($config['class'],'install'))
				$out=call_user_func([$config['class'],'install'],$config);
		}
		return $out;
	}

	/**
	 * add multiple components to the registry
	 * @param $data
	 */
	function addComponents($data, $overwrite=FALSE) {
		$out = [];
		foreach ($data as $key => $settings)
			$out[$key] = $this->addComponent($key,$settings,$overwrite);
		return $out;
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

	/**
	 * return path folder segment from directory path
	 * @param $path
	 * @return mixed
	 */
	function getPackageKeyFromPath($path) {
		$exp = explode('/',rtrim($this->f3->fixslashes($path),'/'));
		return array_pop($exp);
	}
}