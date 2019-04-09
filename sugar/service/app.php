<?php

namespace Sugar\Service;


use Sugar\Config;
use Sugar\Storage;

class App {

	protected
		$f3,
//		$graph,
		$path;

	function __construct() {
		/** @var \Base $f3 */
		$this->f3 = \Base::instance();
		$this->path = $this->f3->get('CORE.apps_path');

		Storage::instance()->load([
			'type'=>'JIG',
			'dir' => $this->f3->get('CORE.data_path'),
			'format' => 'json',
			'lazy' => false
		],'core');
	}

	/**
	 * add an application to the system
	 * @param $key
	 * @param array $params
	 * @param bool $newDefault
	 */
	function add($key,$params=[],$newDefault=FALSE) {

		$app_conf = array_merge([
			'type' => 'app'
		],$params);

		// default app path based on key within global apps dir
		$app_path = $this->path.rtrim($key,'/').'/';

		// custom app path
		if (isset($params['path'])) {
			$app_path = rtrim($params['path'],'/').'/';
			// if not absolute path, add global app dir
			if ($params['path'][0] != '/')
				$app_path = $this->path.$app_path;
		}

		$app_conf['path'] = $app_path;

		// check application path
		if (!is_dir($app_path))
			$this->f3->error(400,sprintf('App directory "%s" not found',$app_path));

		$package_path = $app_path.'package.ini';

		if (!file_exists($package_path))
			$this->f3->error(400,sprintf('No app package file was found at "%s"',$package_path));

		// backup APP key
		if ($this->f3->exists('APP')) {
			$this->f3->copy('APP','APP_bak');
			$this->f3->clear('APP');
		}
		// load apps package.ini as config
		$this->f3->config($package_path);

		// apply loaded package config to local config
		if (!$this->f3->exists('APP',$package_conf)) {
			$this->f3->error(400,'The package.ini seems to be empty.');
		} else {
			$app_conf = array_merge($package_conf,$app_conf);
			if (empty($app_conf['name']))
				$app_conf['name'] = $key;
		}

		// restore backup
		$this->f3->clear('APP');
		if ($this->f3->exists('APP_bak')) {
			$this->f3->copy('APP_bak','APP');
		}

		// save to config
		$config = Config::instance();
		if (!$config->exists('apps'))
			$config->apps = [];

		$config->apps[$key]=$app_conf;

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

		if ($path_exists) {

			if (!file_exists($app_path.'config.ini'))
				$this->f3->write($app_path.'config.ini','');

			if (!file_exists($app_path.'package.ini')) {

				if (empty($params['class'])) {
					$params['class'] = 'Controller\App';
					if (!is_dir($app_path.'controller/'))
						mkdir($app_path.'controller/',\Base::MODE,TRUE);
					if (!file_exists($app_path.'controller/app.php')) {
						$file = <<< PHP
<?php

namespace Controller;

class App extends \Sugar\App {

	function load() {

	}

	function run() {
		echo "Hello world";
	}
}
PHP;
						$this->f3->write($app_path.'controller/app.php',$file);
					}
				}

				$dummy = '[APP]'.PHP_EOL;
				$dummy.= 'class = '.$params['class'].PHP_EOL;
				$dummy.= 'meta.title = '.(isset($params['name'])?$params['name']:$key).PHP_EOL;
				$dummy.= 'meta.version = 0.1.0'.PHP_EOL;
				$dummy.= 'meta.author = '.PHP_EOL;
				$this->f3->write($app_path.'package.ini',$dummy);
			}

			$this->add($key,$params,$newDefault);
		} else {
			$this->f3->error('400','unable to create app directory.');
		}

	}

	/**
	 * @param $key
	 * @return bool
	 */
	function remove($key) {
		$config = Config::instance();
		$existing = isset($config->apps[$key]);
		unset($config->apps[$key]);
		if ($config->default_app == $key) {
			$config->default_app = NULL;
		}
		$config->save();
		return $existing;
	}

	/**
	 * set new default app
	 * @param $key
	 * @return bool success
	 */
	function setDefault($key) {
		$cfg = Config::instance();
		if (!isset($cfg['apps'][$key])) {
			return false;
		} else {
			$cfg->default_app = $key;
			$cfg->persist();
			return TRUE;
		}
	}

	/**
	 * select application based on environmental settings
	 * @return int|null|string
	 */
	function select() {
		$config = Config::instance();
		$enabled=NULL;
		foreach ($config->apps as $key=>$app)
			if (isset($app['enable_on']))
				foreach ($app['enable_on']?:[] as $type => $value) {
					if ($type == 'path' && preg_match('/^\/'.preg_quote($r=trim($value,'/'),'/')
							.'(?:$|\/.*)/iu',$this->f3->get('PATH'))) {
						$enabled = $key;
						$this->f3->set('APP.ROUTE',$r);
					}
					elseif ($type=='host' && $this->f3->get('HOST') == $value)
						$enabled = $key;
				}
		return $enabled;
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
			else $this->f3->error(400,'No default application defined.');
		}

		if (!array_key_exists($key,$config->apps)) {
			$this->f3->error(400,sprintf('No configuration found for application "%s"',$key));
			return FALSE;
		}

		// load defaults into APP var
		$this->f3->extend('APP',$this->f3->get('CORE.app.defaults'),true);


		$app = $config->apps[$key];
		$this->f3->set('CORE.active_app',$app);
		$this->f3->set('CORE.active_app.key',$key);
		$this->f3->copy('CORE.active_app.path','APP.PATH');

		// add app libs to autoloader
		$this->f3->concat('AUTOLOAD',';'.$app['path']);

		// load application config
		$this->f3->config($app['path'].'config.ini');

		// additional custom autoload paths
		if (!$this->f3->devoid('APP.AUTOLOAD',$al))
			$this->f3->concat('AUTOLOAD',';'.(is_array($al)?implode(';',$al):$al));

		// additional language dictionaries
		if (!$this->f3->devoid('APP.LOCALES',$loc)) {
			$locs = array_map(function($val) use ($app) { return $app['path'].$val; },$this->f3->split($loc));
			$this->f3->set('LOCALES',implode(';',$locs).';'.$this->f3->get('LOCALES'));
		}

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
			// config registry
			$reg = Registry::instance(new \Sugar\Storage\Simple\Hive('COMPONENTS'));

			// json db registry
//			$reg = Registry::instance(new \Sugar\Storage\Simple\Jig(
//				Storage::instance()->get('core'),'registry.json','name'));

			if ($this->f3->exists('DIC.preload',$preload))
				$reg->mapClassesToComponents($preload);

			if ($this->f3->exists('DIC.rules',$rules))
				$reg->addDicRules($rules);

			switch ($app['type']) {
				case 'controller':
					// run the defined handler
					$this->f3->call($app['run'],[$this->f3]);
					break;

				case 'app':
					// ad-hoc configure via registry
					/** @var App $app
					 */
					$app = $reg->create($app['name'],$app);
					$app->load();
					$app->run();
					break;

				case 'flow':
//				$this->f3->call($app['run'],[$app['name']]);
//				$this->graph->run('ReadFile.in.source');
					break;
			}

		} else {
			$this->f3->error(400, 'No application loaded');
		}

	}
}