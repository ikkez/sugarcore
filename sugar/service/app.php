<?php

namespace Sugar\Service;


use Sugar\AppInterface;
use Sugar\Component;
use Sugar\Component\Registry;
use Sugar\Config;
use Sugar\Storage;

class App {

	protected
		$f3,
		$graph,
		$path;

	function __construct() {
		/** @var \Base $f3 */
		$this->f3 = \Base::instance();
		$this->path = $this->f3->get('CORE.apps_path');
	}

	/**
	 * add an application to the system
	 * @param $key
	 * @param array $params
	 * @param bool $newDefault
	 */
	function add($key,$params=[],$newDefault=FALSE) {
		$app_path = $this->path.rtrim($key,'/').'/';

		// file system checks
		if (!is_writeable($this->path))
			trigger_error('Application folder is not writeable',E_USER_ERROR);

		// check application path
		$path_exists = is_dir($app_path);

		// save to config
		$config = Config::instance();
		if (!$config->exists('apps'))
			$config->apps = [];

		$config->apps[$key] = array_merge([
			'name' => $key,
			'path' => $path_exists ? $app_path : FALSE,
			'type' => 'app'
		],$params);

		// make default
		if ($newDefault) {
			$config->default_app = $key;
		}

		$config->save();
	}

	/**
	 * create application folder and register app
	 * @param $key
	 * @param array $params
	 * @param bool $newDefault
	 */
	function create($key,$params=[],$newDefault=FALSE) {
		$app_path = $this->path.rtrim($key,'/').'/';

		// file system checks
		if (is_dir($app_path))
			trigger_error('Application folder already exists',E_USER_NOTICE);

		if (!is_writeable($this->path))
			trigger_error('Application folder is not writeable',E_USER_ERROR);


		// create application path
		if (!is_dir($app_path))
			$path_exists = mkdir($app_path,\Base::MODE,TRUE);
		else
			$path_exists=true;

		if ($path_exists && !file_exists($app_path.'config.ini')) {
			$this->f3->write($app_path.'config.ini','');
		}
		$this->add($key,$params,$newDefault);
	}

	/**
	 * @param $key
	 */
	function remove($key) {
		$config = Config::instance();
		unset($config->apps[$key]);
		$config->save();
	}

	/**
	 * load application configuration into the system
	 *
	 * @param $key
	 * @return bool
	 */
	function load($key=NULL) {
		$config = Config::instance();

		// load default application
		if (!$key && $this->f3->get('CORE.load_default_app')) {
			$config = Config::instance();
			if (!empty($config->default_app))
				$key = $config->default_app;
			else $this->f3->error(500,'No default application defined.');
		}

		if (!array_key_exists($key,$config->apps)) {
			$this->f3->error(500,sprintf('No configuration found for application "%s"',$key));
			return FALSE;
		}

		$app = $config->apps[$key];
		$this->f3->set('CORE.active_app',$app);

		// add app libs to autoloader
		$this->f3->concat('AUTOLOAD',';'.$app['path']);

		// load application config
		$this->f3->config($app['path'].'config.ini');

//		if ($app['type']=='flow') {
//			$graph = new \Sugar\Flow\Graph($this->f3->get('CORE.active_app.name'));
//
////			$graph->build($this->f3->get('APP.graph'));
//			$cmds = $graph->parse($app['path'].'app.ini');
//			$graph->build($cmds);
//			$this->graph = $graph;
//		}

		return TRUE;
	}

	/**
	 * bootstrap application
	 */
	function run() {

		// run active application
		if ($this->f3->exists('CORE.active_app',$app)) {

			// init component registry
			$reg = Registry::instance(new \Sugar\Storage\Simple\Hive('COMPONENTS'));

//			$reg = new Registry(new \Sugar\Storage\Simple\Jig(Storage::instance()->load([
//				'type'=>'JIG',
//				'dir' => $this->f3->get('CORE.data_path'),
//				'format' => 'json',
//				'lazy' => true
//			],'core_db'),'registry.json','name'));

			$reg->preload();

			switch ($app['type']) {
				case 'controller':
					// run the defined handler
					$this->f3->call($app['run'],[$this->f3]);
					break;

				case 'app':
					// ad-hoc configure via registry
					/** @var AppInterface $app */
					$app = $reg->create($app['name'],$app);
					$app->run();
					break;

				case 'flow':
//				$this->f3->call($app['run'],[$app['name']]);
//				$this->graph->run('ReadFile.in.source');
					break;
			}

		} else {
			$this->f3->error(500, 'No application loaded');
		}

	}
}