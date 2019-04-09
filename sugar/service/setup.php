<?php

namespace Sugar\Service;


class Setup extends \Prefab {

	/**
	 * check environment for requirements
	 * @return array
	 */
	public function preflight() {
		/** @var \Base $f3 */
		$f3 = \Base::instance();

		$checkWriteableDirs = [
			$f3->TEMP,
			$f3->LOGS,
//			$f3->UPLOADS,
//			'res/',
			$f3->get('CORE.data_path'),
			$f3->get('CORE.apps_path'),
//			$f3->get('ASSETS.public_path'),
		];


		$test = new \Test();

		$test->expect((PHP_VERSION_ID >= 50407),'PHP version: '.PHP_VERSION.' >= 5.4.7');
		$test->expect((float)PCRE_VERSION>=8.0,'PCRE version: '.(float)PCRE_VERSION.' >= 8.0');

		foreach ($checkWriteableDirs as $dir) {
			$test->expect(is_dir($dir),sprintf("Directory '%s' exists", $dir));
			$test->expect(is_writable($dir),sprintf("Directory '%s' is writable", $dir));
		}

		$checkWriteableFiles = [
			$f3->get('CORE.data_path').'config.json',
			$f3->get('CORE.data_path').'registry.json',
		];

		foreach ($checkWriteableFiles as $file) {
			$test->expect(file_exists($file),sprintf("File '%s' exists", $file));
			$test->expect(is_writable($file),sprintf("File '%s' is writable", $file));
		}

		$test->expect($f3->ROOT==$f3->get('SERVER.DOCUMENT_ROOT'),'ROOT equals SERVER.DOCUMENT_ROOT');
		$test->expect($f3->get('SERVER.SERVER_NAME'),'SERVER_NAME is defined: '.$f3->get('SERVER.SERVER_NAME'));


		return $test->results();
	}



}