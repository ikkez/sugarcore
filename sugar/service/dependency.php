<?php

/**
 *  Sugarcore - Composer-based Dependency Manager
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


class Dependency extends \Prefab {

	protected
		$f3,
		$registry,
		$config;

	function __construct(Registry $registry) {
		/** @var \Base $f3 */
		$this->f3 = \Base::instance();
		$this->registry = $registry;
		$this->config = \Sugar\Config::instance();
	}

	/**
	 * test and unify certain dependency configuration strings
	 * @param $config
	 * @return array
	 */
	protected function resolveDependenciesFromConfig($config) {
		$out = [];
		if ($config) {
			if (isset($config['depends']['composer']['require'])) {
				$composer = $config['depends']['composer'];
				if (!is_array($composer['require'])) {
					if (is_int(strpos($composer['require'],':'))) {
						list($key,$version) = explode(':',$composer['require']);
						$composer['require'] = [$key=>$version];
					} else {
						$this->f3->error(500,'Unable to detect composer requirement for '.$name.': '.$composer['require']);
					}
				} else {
					foreach ($composer['require'] as $key => $version) {
						if (is_int($key)) {
							if (is_array($version)) {
								$composer['require'][$version[0]] = $version[1];
								unset($composer['require'][$key]);
							} else {
								if (is_int(strpos($version,':'))) {
									list($k,$v) = explode(':',$version);
									$composer['require'][$k]=$v;
									unset($composer['require'][$key]);
								} else {
									$this->f3->error(500,'Unable to detect composer requirement for '.$key.': '.$version);
								}
							}
						}
					}
				}
				$out['composer']=$composer;
			}
		}
		return $out;
	}

	/**
	 * returns unified dependency array from a given component name
	 * @param $name
	 * @return array
	 */
	function getDependenciesFromComponent($name) {
		$config = $this->registry->exists($name);
		return $this->resolveDependenciesFromConfig($config);
	}

	/**
	 * add component dependencies to global config
	 * @param $name
	 */
	function addComponentDependencies($name) {
		$config = $this->registry->exists($name);
		$this->addDependenciesFromConfig($config);
	}

	/**
	 * add dependency array to global config
	 * @param $conf
	 */
	function addDependenciesFromConfig($conf) {
		$conf = $this->resolveDependenciesFromConfig($conf);
		if (!$this->config->exists('dependencies')) {
			$this->config->dependencies=[];
		}
		$all = array_replace_recursive($this->config->dependencies,$conf);
		$this->config->dependencies = $all;
		$this->config->save();
	}

	/**
	 * run updater
	 */
	function updateDependencies() {
		$comp_data=[];
		if (isset($this->config->dependencies['composer']))
			$comp_data=$this->config->dependencies['composer'];
		$this->writeComposerFile($comp_data);
		if (!$this->verifyComposerPhar())
			$this->f3->error(500,'composer installer not available, please run installer');
		$this->runComposerCommand('update');
	}

	/**
	 * check if composer file is present
	 * @return bool
	 */
	function verifyComposerPhar() {
		return file_exists(EXT_LIB.'bin/composer.phar');
	}

	/**
	 * write down composer config file with given data
	 * @param $data
	 */
	function writeComposerFile($data) {
		$composer = [
			"config" => [
				"vendor-dir"=> "src"
			],
			"replace" => [
				// skip this dependency, as it's already provided by the core repo
				"bcosca/fatfree-core" => '3.*'
			]
		]+$data;
		$conf_json = json_encode($composer,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		file_put_contents(EXT_LIB.'composer.json',$conf_json);
	}

	/**
	 * run composer command
	 * @param $command
	 * @return false|string
	 * @throws \Exception
	 */
	function runComposerCommand($command) {
		$root=$this->f3->ROOT.'/';
		$lib_dir = $root.EXT_LIB;
		$composer_phar = $root.EXT_LIB.'bin/composer.phar';
		require_once "phar://{$composer_phar}/src/bootstrap.php";

		@ini_set('memory_limit',-1);
		if ((int)ini_get('max_execution_time')<300)
			@ini_set('max_execution_time',300);

		$bak_dir=getcwd();
		chdir($lib_dir);
		putenv("COMPOSER_HOME={$lib_dir}");
		putenv("COMPOSER_CACHE_DIR=".$root.EXT_LIB.'cache/');
		putenv("COMPOSER_DISABLE_XDEBUG_WARN=1");

		if (!$this->f3->CLI) {
			ob_start();
			putenv("OSTYPE=OS400"); //force to use php://output instead of php://stdout
		}

		$app = new \Composer\Console\Application();
		$factory = new \Composer\Factory();
		$output = $factory->createOutput();

		$input = new \Symfony\Component\Console\Input\ArrayInput(array(
			'command' => $command,
		));
		$input->setInteractive(false);

		if (function_exists('xdebug_disable'))
			xdebug_disable();

		$cmdret = $app->doRun($input,$output);

		chdir($bak_dir);
		if (!$this->f3->CLI)
			return ob_get_clean();
	}
}