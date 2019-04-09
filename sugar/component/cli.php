<?php

namespace Sugar\Component;


use Sugar\Service\App;
use Sugar\Service\Registry;
use Sugar\Service\Setup;

class CLI {

	const E_UNKNOWN_COMMAND = "Unknown command argument";

	protected $cli_version = '0.8.3';

	protected $cli;

	function __construct() {
		$this->cli = \Sugar\Utility\CLI::instance();;
	}

	/**
	 * home screen
	 * @param \Base $f3
	 * @param $params
	 */
	public function index(\Base $f3, $params) {
		$tasks=[
			'app' => 'Manage or create local apps',
			'clear' => 'clean-up utilities',
			'help' => 'Provides help for a certain command',
			'preflight' => 'run a system check to find common installation/server issues',
		];

		$out = $this->cli->string("available tasks:",null,null,'bold').PHP_EOL.PHP_EOL;

		foreach ($tasks as $task => $label) {
			$out.= $this->cli->paddedItem($task,$label);
		}
		echo $out;
	}


	/**
	 * app management
	 * @param \Base $f3
	 * @param $params
	 */
	public function app(\Base $f3, $params) {
		$params += [
			'arg1'=>NULL,
			'arg2'=>NULL,
			'arg3'=>NULL,
		];

		if (!$params['arg1']) {
			$this->help($f3,['arg1'=>'app']);
		}
		else {
			$app = new \Sugar\Service\App();
			$cfg = \Sugar\Config::instance();

			if ($params['arg1']=='list') {
				echo $this->cli->string('  Key                     Name             ',null,null,'underline').PHP_EOL;
				echo PHP_EOL;
				foreach ($cfg['apps'] as $key=>$app) {
					echo $this->cli->paddedItem($key.($cfg['default_app']==$key?' [default]':''),$app['name'].' [type: '.$app['type'].']').PHP_EOL;
				}
			}

			elseif ($params['arg1'] == "create") {

				$opt = [
					'type'=>'app',
				];

				if ($f3->exists('GET.class',$class)) {
					$opt['class'] = $class;
				}

				if ($f3->exists('GET.name',$name))
					$opt['name'] = $name;

				$app->create($params['arg2'],$opt,false);
				echo $this->cli->successString('App created successfully');

			}

			elseif ($params['arg1'] == "add") {

				$opt = [
					'type'=>''
				];

				if ($f3->exists('GET.legacy',$cntl)) {
					$opt['type'] = 'controller';
					$opt['run'] = $cntl;
					if(empty($cntl)) {
						echo $this->cli->errorString(sprintf('Attribute `%s` should not be empty. You need to define a callable string like `Foo\Controller->handler`','legacy'));
						return;
					}
				}

				if ($f3->exists('GET.name',$name)) {
					if (empty($name)) {
						echo $this->cli->errorString(sprintf('Attribute `%s` should not be empty','name'));
						return;
					}
					$opt['name'] = $name;
				}

				if ($f3->exists('GET.path',$path)) {
					if (empty($path)) {
						echo $this->cli->errorString(sprintf('Attribute `%s` should not be empty','path'));
						return;
					}
					$opt['path'] = $path;
				}

				if (empty($opt['type'])) {
					$opt['type']='app';
				}

				$app->add($params['arg2'],$opt,false);
				echo $this->cli->successString('App added');
			}

			elseif ($params['arg1'] == "default") {
				if (empty($params['arg2'])) {
					echo $this->cli->errorString('No app key was given.');
				} else {
					if ($app->setDefault($params['arg2']))
						echo $this->cli->successString(sprintf('New default app is now: `%s`',$cfg->default_app));
					else
						echo $this->cli->errorString('This app key does not exist. Unable to set new default app.');
				}
			}

			elseif ($params['arg1'] == "remove") {
				if (empty($params['arg2'])) {
					echo $this->cli->errorString('No app key was given.');
				} else {
					if ($app->remove($params['arg2']))
						echo $this->cli->successString(sprintf('App `%s` successfully removed from registry.',$params['arg2']));
					else
						echo $this->cli->errorString(sprintf('App `%s` was not found in registry. ',$params['arg2']));
				}
			} else {
				echo $this->cli->errorString(self::E_UNKNOWN_COMMAND);
			}
		}

	}

	public function clear(\Base $f3, $params) {
		$params += [
			'arg1'=>NULL,
			'arg2'=>NULL,
			'arg3'=>NULL,
		];
		if (!$params['arg1']) {
			$this->help($f3,['arg1'=>'clear']);
			return;
		}
		if ($params['arg1'] == 'cache') {
			$cache = \Cache::instance();
			$seed = NULL;
			if (!$f3->exists('GET.app',$app)) {
				if (!$f3->exists('GET.seed',$seed)) {
					$seed = $f3->SEED;
					echo $this->cli->errorString('No app or seed specified. Using default, which is probably useless.').PHP_EOL;
				}
			}
			else {
				$service = new App();
				$service->load($app);
				$seed = $f3->SEED;
			}

			$dns = $cache->load(TRUE,$seed).' ['.$seed.'.*]';


			echo $this->cli->string('Clearing cache now: '.$dns,'yellow').PHP_EOL;
			sleep(1);
			echo $this->cli->string('3...','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('2..','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('1.','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('Clearing...','yellow',null,'invert,blink').PHP_EOL;
			echo PHP_EOL;
			sleep(1);
			$cache->reset();
			echo 'Clearing cache: '.$this->cli->string('Done','green',null,'bold');
		}
		elseif ($params['arg1'] == 'template') {
			if (!$f3->exists('GET.seed',$seed))
				$seed = $f3->SEED;
			$all=false;
			if ($f3->exists('GET.all',$all))
				$all = true;
			echo $this->cli->string('Removing template cache: '.($all?'all':$seed),'yellow').PHP_EOL;
			sleep(1);
			echo $this->cli->string('3...','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('2..','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('1.','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('Clearing...','yellow',null,'invert,blink').PHP_EOL;
			echo PHP_EOL;
			sleep(1);
			$i=0;
			foreach (glob($f3->get('TEMP').($all?'':$seed.'.').'*.php') as $file) {
				$i++;
				unlink($file);
			}
			echo 'Clearing pre-compiled template: '.$this->cli->string('Done','green',null,'bold').' '.$i.' files removed';

		}
		elseif ($params['arg1'] == 'assets') {
			if (!$f3->exists('GET.app',$app)) {
				echo $this->cli->errorString('No app specified. Aborting.');
				return;
			}
			else {
				$service = new App();
				// mock app load
				$service->load($app);
			}
			$reg = Registry::instance(new \Sugar\Storage\Simple\Hive('COMPONENTS'));
			// create dummy Assets manager to load config
			$tmpl_assets = $reg->create('TemplateAssets');

			$path = $f3->get('ASSETS.public_path');
			if (empty($path)) {
				echo $this->cli->errorString('Public path is empty. Stopping here.').PHP_EOL;
				return;
			}

			echo $this->cli->string('Removing assets cache: '.$path,'yellow').PHP_EOL;
			sleep(1);
			echo $this->cli->string('3...','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('2..','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('1.','yellow',null).PHP_EOL;
			sleep(1);
			echo $this->cli->string('Clearing...','yellow',null,'invert,blink').PHP_EOL;
			echo PHP_EOL;
			sleep(1);
			$i = $tmpl_assets->assets()->clear();
			echo 'Clearing assets cache: '.$this->cli->string('Done','green',null,'bold').' '.$i.' files removed';

		} else {
			echo $this->cli->errorString(self::E_UNKNOWN_COMMAND).PHP_EOL;
			$this->help($f3,['arg1'=>'clear']);
		}
	}


	/**
	 * display help
	 * @param \Base $f3
	 * @param $params
	 */
	public function help(\Base $f3, $params) {
		$params += [
			'arg1'=>NULL,
			'arg2'=>NULL,
			'arg3'=>NULL,
		];

		$arg1 = $params['arg1'];
		$arg2 = $params['arg2'];
		$arg3 = $params['arg3'];

		if (!$arg1) {
			echo $this->cli->string('To get help about a certain command, specify a topic.','red').PHP_EOL;
			echo PHP_EOL;
			echo $this->cli->paddedItem('help <task>','Display help description for a command');
			echo PHP_EOL;
		} else {
			switch($arg1) {
				case 'app':
					echo $this->cli->paddedItem('app add [key]','This adds an existing App to the registry. <key> should be the directory name within global apps folder.');
					echo $this->cli->paddedSubItem('[--name=<name>]','Set a custom component name for this app');
					echo $this->cli->paddedSubItem('[--path=<path>]','Specify a different path to the app folder. This can also be absolute.');
					echo $this->cli->paddedSubItem('[--legacy=<call>]','use legacy mode and just define a callable string that should be run, i.e. `--legacy="Foo->bar"`');
					echo PHP_EOL;
					echo $this->cli->paddedItem('app create [key]','Create a new app with basic folder structure, generate `package.ini` and add it to the registry.');
					echo $this->cli->paddedSubItem('[--name=<name>]','Set a custom component name for this app');
					echo $this->cli->paddedSubItem('[--class=<class>]','Use an existing class as app front-controller. Default: Controller\App');
					echo PHP_EOL;
					echo $this->cli->paddedItem('app default [key]','Set a default app to be executed');
					echo $this->cli->paddedItem('app list','get a list of registered apps');
					echo $this->cli->paddedItem('app remove [key]','This removes an app from the registry. It does not remove any files.');
				break;
				case 'clear':
					echo $this->cli->paddedItem('clear cache','Clean-up the system\'s default cache storage');
					echo $this->cli->paddedSubItem('[--seed=<key>]','Set a specific seed key to be cleared');
					echo PHP_EOL;
					echo $this->cli->paddedItem('clear template','Erase pre-compiled template files');
					echo $this->cli->paddedSubItem('[--seed=<key>]','Set a specific seed key to be cleared');
					echo $this->cli->paddedSubItem('[--all]','Force removal of all file');
					echo PHP_EOL;
					echo $this->cli->paddedItem('clear assets','Erase temporary CSS/JS asset files');
					echo $this->cli->paddedSubItem('--app','specify which app to handle');
					break;

				default:
					echo $this->cli->string('No help available about this topic :(','red');
			}

		}
	}

	function preflight(\Base $f3, $params) {

		$setup = Setup::instance();

		$passed = 0;
		$results = $setup->preflight();
		$test_i = 1;
		foreach ($results as $test) {
			if ($test['status']) {
				$passed++;
				echo $this->cli->string(' PASS ','green',NULL,'bold,invert').' ';
			} else {
				echo $this->cli->string(' FAIL ','red',NULL,'bold').' ';
			}
				echo '#'.$test_i++.': ';
				echo $this->cli->string($test['text']).PHP_EOL;
			usleep(50*1000);
		}

		echo PHP_EOL;
		$final_msg = $passed.' / '.count($results).' tests passed';
		echo $this->cli->string(($passed == count($results) ?'OK: ':'Warning: '),null,null,'bold');
		echo $this->cli->string($final_msg).PHP_EOL;
	}

	function beforeroute(\Base $f3) {

		$this->cli->noBuffer();
		$this->cli->clear();

		echo $this->cli->string(' ___ ____','magenta').PHP_EOL;
		echo $this->cli->string('| __|__ /','magenta').PHP_EOL;
		echo $this->cli->string('| _| |_ \\','magenta').PHP_EOL;
		echo $this->cli->string('|_| |___/ ','magenta');
		echo $this->cli->string('  Sugarcore CLI ','magenta',null,'bold,underline');
		echo $this->cli->string('v'.$this->cli_version.' ','magenta',null,'underline').PHP_EOL;
//		if ($f3->ALIAS == 'index')
		echo PHP_EOL.$this->cli->string(" Usage: php sugar task args --attr=\"value\" ",'magenta',null,'bold,invert').PHP_EOL;
		echo $this->cli->string("===========================================",'magenta',null,'faint').PHP_EOL.PHP_EOL;
	}


	function afterroute(\Base $f3) {
		echo PHP_EOL.PHP_EOL;
	}
	
}