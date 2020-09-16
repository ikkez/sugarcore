<?php

namespace Sugar\Service;


use Sugar\Config;

class Setup extends \Prefab {

	/**
	 * check environment for requirements
	 * @return array
	 */
	public function preflight() {
		/** @var \Base $f3 */
		$f3 = \Base::instance();

		$checkWriteableDirs = $this->getWriteableDirs();

		$test = new \Test();

		$test->expect((PHP_VERSION_ID >= 50407),'PHP version: '.PHP_VERSION.' >= 5.4.7');
		$test->expect((float)PCRE_VERSION>=8.0,'PCRE version: '.(float)PCRE_VERSION.' >= 8.0');
		if (!$f3->CLI)
			$test->expect(ini_get('max_execution_time') >= 60,'php: max_execution_time: '.ini_get('max_execution_time').' >= 60');
		$test->expect((int)ini_get('memory_limit') >= 32,'php: memory_limit: '.ini_get('memory_limit').' >= 32M');

		foreach ($checkWriteableDirs as $dir) {
			$test->expect(file_exists($dir) && is_dir($dir),sprintf("Directory '%s' exists", $dir));
			$test->expect(is_writable($dir),sprintf("Directory '%s' is writable", $dir));
		}

		$checkWriteableFiles = $this->getWriteableFiles();

		foreach ($checkWriteableFiles as $file) {
			$test->expect(file_exists($file),sprintf("File '%s' exists", $file));
			$test->expect(is_writable($file),sprintf("File '%s' is writable", $file));
		}

		$test->expect($f3->ROOT==$f3->get('SERVER.DOCUMENT_ROOT'),'ROOT equals SERVER.DOCUMENT_ROOT');
		$test->expect($f3->get('SERVER.SERVER_NAME'),'SERVER_NAME is defined: '.$f3->get('SERVER.SERVER_NAME'));

		return $test->results();
	}

	/**
	 * create files and folders
	 */
	public function install_base() {
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		$dirs = $this->getWriteableDirs();
		$logs=[];
		foreach ($dirs as $path) {
			if (!file_exists($path)){
				$logs[]='Creating directory: '.$path;
				mkdir($path,0775,TRUE);
			}
			elseif (!is_writable($path)) {
				$logs[]='Changing write access: '.$path;
				chmod($path,0775);
			}
		}
		if (!file_exists($config_file=$f3->get('CORE.data_path').'config.json')) {
			$logs[]='Creating config: '.$config_file;
			$conf = Config::instance();
			$conf->apps = [];
			$conf->dependencies = [];
			$conf->default_app = null;
			$conf->save();
		}
		$files = $this->getWriteableFiles();
		foreach ($files as $path) {
			if (!file_exists($path)) {
				$logs[]='Creating file: '.$path;
				file_put_contents($path,'{}');
			}
		}
		return $logs;
	}

	/**
	 * return folders that should be writeable for the system
	 * @return array
	 */
	public function getWriteableDirs() {
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		return [
			$f3->get('TEMP'),
			$f3->get('LOGS'),
			$f3->get('CORE.data_path'),
			$f3->get('CORE.apps_path'),
			$f3->get('CORE.repo_path'),
		];
	}

	/**
	 * return files that should be writeable for the system
	 * @return array
	 */
	public function getWriteableFiles() {
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		return [
			$f3->get('CORE.data_path').'config.json',
			$f3->get('CORE.data_path').'registry.json',
		];
	}


}