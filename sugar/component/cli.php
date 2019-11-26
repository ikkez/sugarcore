<?php

namespace Sugar\Component;


use Sugar\Service\App;
use Sugar\Service\Dependency;
use Sugar\Service\Registry;
use Sugar\Service\Repository;
use Sugar\Service\Setup;
use Sugar\Storage;

class CLI {

	const E_UNKNOWN_COMMAND = "Unknown command argument";

	protected $cli_version = '0.8.5';

	protected $cli;

	protected $reg;

	function __construct() {
		$this->cli = \Sugar\Utility\CLI::instance();

		Storage::instance()->load([
			'type'=>'JIG',
			'dir' => \Base::instance()->get('CORE.data_path'),
			'format' => 'json',
			'lazy' => false
		],'core');

		// init component registry
		// TODO: use from App Service
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		if (!$f3->exists('CORE.registry_type',$reg_type) || strtolower($reg_type)=='hive') {
			$f3->set('CORE.registry_type','hive');
			$this->reg = Registry::instance(new \Sugar\Storage\Simple\Hive('COMPONENTS'));

		} elseif (strtolower($reg_type)=='jig') {
			$this->reg = Registry::instance(new \Sugar\Storage\Simple\Jig(
				Storage::instance()->get('core'),'registry.json','name'));
			$this->reg->enableHIVEComponents();
		}

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
			'setup' => 'create basic system structure',
			'registry' => 'tools to manage the component registry',
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
				echo $this->cli->string('  Key                     Info             ',null,null,'underline').PHP_EOL;
				echo PHP_EOL;
				foreach ($cfg['apps'] as $key=>$app) {
					echo $this->cli->paddedItem($key.($cfg['default_app']==$key?' [default]':''),$app['name'].' ['.$app['type'].', v'.$app['meta']['version'].']').PHP_EOL;
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

				$make_default = empty($cfg['apps']);
				$app->create($params['arg2'],$opt,$make_default);
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

				$make_default = empty($cfg['apps']);
				$app->add($params['arg2'],$opt,$make_default);
				echo PHP_EOL.$this->cli->successString('App added');
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

	/**
	 * clean-up tools
	 * @param \Base $f3
	 * @param $params
	 */
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
			// create dummy Assets manager to load config
			$tmpl_assets = $this->reg->create('TemplateAssets');

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
	 * Registry
	 * @param \Base $f3
	 * @param $params
	 */
	function registry(\Base $f3, $params) {
		$params += [
			'arg1'=>NULL,
			'arg2'=>NULL,
			'arg3'=>NULL,
		];
		if (!$params['arg1']) {
			$this->help($f3,['arg1'=>'registry']);
			return;
		}

		if ($params['arg1'] == 'info') {
			echo $this->cli->string('Registry information:',NULL,null,'underline').PHP_EOL.PHP_EOL;
			echo $this->cli->string('Storage type:',NULL,null,'bold').' '.$f3->get('CORE.registry_type').PHP_EOL;
			echo $this->cli->string('Loaded components:',NULL,null,'bold').' '.count($this->reg->getModel()->getAll()).PHP_EOL;


		} elseif ($params['arg1'] == 'scan') {

			echo 'Scanning for new packages and components in: '.$this->cli->string($f3->get('CORE.repo_path'),NULL,null,'bold').PHP_EOL.PHP_EOL;
			sleep(1);
			$repo = new Repository($this->reg);
			$results = $repo->findPackages();
			foreach ($results as $package_path) {
				usleep(50*1000);
				$comps = $repo->loadPackage($f3->get('CORE.repo_path').$package_path);
				if ($comps) {
					$new=[];
					foreach ($comps as $key => $comp) {
						if (!$this->reg->exists($key)) {
							if (!isset($new[$package_path]))
								$new[$package_path]=[];
							$new[$package_path][$key]=$comp;
						}
					}
					if ($new) {
						foreach ($new as $package => $comps) {
							$package_key = $repo->getPackageKeyFromPath($package);
							$package_line=str_replace($package_key,$this->cli->string($package_key,'magenta'),$package);
							echo $this->cli->string('Package: ',NULL,null,'bold').$package_line.PHP_EOL;
							foreach ($comps as $key => $comp) {
								echo $this->cli->paddedItem($key,$comp['name'].', v'.$comp['meta']['version']);
							}
							echo PHP_EOL;
							if ($f3->exists('GET.install')) {
								echo '> Installing '.$this->cli->string($package_key,'magenta',null,'bold').' components.'.PHP_EOL;
								$out = $repo->installPackage($f3->get('CORE.repo_path').$package);
								if ($out)
									foreach ($out as $log) {
										usleep(50*1000);
										if (is_array($out))
											$out = implode(PHP_EOL,$out);
										echo '> '.$log.PHP_EOL;
									}
								echo PHP_EOL;
								echo PHP_EOL.PHP_EOL;
							}
						}
					}
				}
			}
			if ($f3->exists('GET.install')) {
				echo PHP_EOL;
				echo "Updating Dependencies:".PHP_EOL.PHP_EOL;
				sleep(1);
				Dependency::instance($this->reg)->updateDependencies();
				echo PHP_EOL;
			}
			echo $this->cli->string('Done','green',null,'bold,invert');

		} elseif ($params['arg1'] == 'update') {

			if ($f3->exists('GET.cleanup')) {
				echo 'Cleaning up dependency entries. ';
				// TODO: keep app-specific dependencies
				$config = \Sugar\Config::instance();
				if (isset($config->dependencies['composer']))
					$config->dependencies=['composer'=>[]];
				else
					$config->dependencies=[];
				usleep(50*1000);
				$config->save();
				echo 'Done. '.PHP_EOL.PHP_EOL;
			}
			echo $this->cli->string("Updating known components:",null,null,'underline').PHP_EOL.PHP_EOL;
			sleep(1);
			$repo = new Repository($this->reg);
			$all = $this->reg->getModel()->getAll();
			$homes=[];
			$install=[];
			foreach ($all as $comp) {
				usleep(50*1000);
				if ($comp['home']) {
					if (!isset($homes[$comp['home']]))
						$homes[$comp['home']] = $repo->loadPackage($comp['home']);
					$comps = $homes[$comp['home']];
					if ($homes[$comp['home']] && $homes[$comp['home']][$comp['name']]) {
						echo 'Updating: '.$this->cli->string($comp['name'],'green',null,'bold').PHP_EOL;
						$success = $repo->addComponent($comp['name'],$homes[$comp['home']][$comp['name']],TRUE);
						if ($success) {
							$install[]=$comp['name'];
						}
					}
				}
			}
			echo PHP_EOL;
			echo $this->cli->string("Updating Dependencies:",null,null,'underline').PHP_EOL.PHP_EOL;
			sleep(1);
			Dependency::instance($this->reg)->updateDependencies();
			echo PHP_EOL;
			foreach ($install as $key) {
				$success=$repo->installComponent($key);
				if ($success!==NULL) {
					usleep(50*1000);
					if (is_array($success)) {
						foreach ($success as $line)
							$out[] = $line;
						echo '> '.$key.' installed';
					}
					else
						echo '> '.$key.' installed: '.((bool)$success==TRUE?'OK':'FAILED');
					echo PHP_EOL;
				}
			}
			echo PHP_EOL;
			echo $this->cli->successString('Done');

		} elseif ($params['arg1'] == 'add') {

			if (is_int(strpos($params['arg2'],':')))
				list($pathKey,$comp_name) = explode(':',$params['arg2']);
			else {
				$comp_name = $params['arg2'];
				if (!$f3->exists('GET.path',$path)) {
					echo $this->cli->errorString('No package folder given');
					exit();
				}
			}
			if ($f3->exists('GET.path',$path))
				$pathKey = $path;
			echo 'Looking for package: '.$this->cli->string($pathKey,NULL,null,'bold').PHP_EOL;
			sleep(1);
			$pathKey = rtrim($pathKey,'/');
			$repo = new Repository($this->reg);
			if (!file_exists($f3->get('CORE.repo_path').$pathKey.'/'.'package.ini')) {
				$results = $repo->findPackages();
				$found = FALSE;
				foreach ($results as $package_path) {
					if ($pathKey == $repo->getPackageKeyFromPath($package_path)) {
						$package_line=str_replace($pathKey,$this->cli->string($pathKey,'magenta'),$package_path);
						$pathKey=$package_path;
						echo 'Found package at: '.$package_line.PHP_EOL.PHP_EOL;
						$found=TRUE;
						break;
					}
				}
				if (!$found) {
					echo $this->cli->errorString(sprintf('No package folder found with name "%s", nor package.ini found at "%s". Aborting.',$pathKey,$f3->get('CORE.repo_path').$pathKey.'/package.ini'));
					exit();
				}
			}
			$comps = $repo->loadPackage($f3->get('CORE.repo_path').$pathKey);
			if (!isset($comps[$comp_name])) {
				echo $this->cli->errorString(sprintf('No component found with name "%s" within package folder "%s".',$comp_name,$pathKey));
				exit();
			} else {
				$success = $repo->addComponent($comp_name,$comps[$comp_name],TRUE);
				if ($success) {
					echo $this->cli->successString('Component added').PHP_EOL;
					if ($f3->exists('GET.install')) {
						echo 'Installing '.$this->cli->string($comp_name,NULL,null,'bold').''.PHP_EOL;
						$success=$repo->installComponent($comp_name);
						if ($success!==NULL) {
							if (is_array($success)) {
								foreach ($success as $line)
									$out[] = $line;
								echo '> '.$comp_name.' installed';
							}
							else
								echo '> '.$comp_name.' installed: '.((string)(bool)$success);

							echo PHP_EOL.PHP_EOL;
							echo "Updating Dependencies:".PHP_EOL.PHP_EOL;
							sleep(1);
							Dependency::instance($this->reg)->updateDependencies();
						}
					}
				}
			}


		} elseif ($params['arg1'] == 'add-package') {

			if ($f3->exists('GET.path',$path))
				$params['arg2'] = $path;
			echo 'Looking for package: '.$this->cli->string($params['arg2'],NULL,null,'bold').PHP_EOL;
			sleep(1);
			$pathKey = rtrim($params['arg2'],'/');
			$repo = new Repository($this->reg);
			if (!file_exists($f3->get('CORE.repo_path').$pathKey.'/'.'package.ini')) {
				$results = $repo->findPackages();
				$found = FALSE;
				foreach ($results as $package_path) {
					if ($pathKey == $repo->getPackageKeyFromPath($package_path)) {
						$package_line=str_replace($pathKey,$this->cli->string($pathKey,'magenta'),$package_path);
						$pathKey=$package_path;
						echo 'Found package at: '.$package_line.PHP_EOL.PHP_EOL;
						$found=TRUE;
						break;
					}
				}
				if (!$found) {
					echo $this->cli->errorString(sprintf('No package folder found with name "%s", nor package.ini found at "%s". Aborting.',$pathKey,$f3->get('CORE.repo_path').$pathKey.'/package.ini'));
					exit();
				}
			}
			echo 'Installing '.$this->cli->string($pathKey,NULL,null,'bold').' components.'.PHP_EOL;
			$out = $repo->installPackage($f3->get('CORE.repo_path').$pathKey);
			if ($out)
				foreach ($out as $log) {
					usleep(50*1000);
					if (is_array($out))
						$out = implode(PHP_EOL,$out);
					echo '> '.$log.PHP_EOL;
				}

		} elseif ($params['arg1'] == 'install') {
			
			$comp_name = trim($params['arg2']);
			if ($this->reg->exists($comp_name)) {
				$repo = new Repository($this->reg);
				echo 'Installing component: '.$this->cli->string($comp_name,NULL,null,'bold').PHP_EOL;
				$out = $repo->installComponent($comp_name);
				if (is_array($out))
					$out = implode(PHP_EOL,$out);
				echo $out.PHP_EOL;
			} else {
				echo $this->cli->errorString(sprintf('Unknown component "%s". Add it to the registry first.',$comp_name)).PHP_EOL;
			}

		} elseif ($params['arg1'] == 'list') {
			$all = $this->reg->getModel()->getAll();
			ksort($all);
			foreach ($all as $key=>$comp) {
				echo $this->cli->paddedItem($key,$comp['meta']['title'].', v'.$comp['meta']['version']);
			}

		}  elseif ($params['arg1'] == 'remove') {
			$comp_name = $params['arg2'];
			if ($this->reg->exists($comp_name)) {
				$repo = new Repository($this->reg);
				$repo->removeComponent($comp_name);
				echo "Removing component ".$this->cli->string($comp_name,NULL,null,'bold').PHP_EOL;
				echo "Done";
			} else {
				echo $this->cli->errorString(sprintf('Unknown component "%s".',$comp_name)).PHP_EOL;
			}

		} else {
			$this->help($f3,['arg1'=>'registry']);
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
				case 'registry':
					echo $this->cli->string('  registry','cyan',null,'bold').PHP_EOL.PHP_EOL;
					echo $this->cli->paddedItem('info','Show some detail information');
					echo $this->cli->paddedItem('list','Show all active components');
					echo $this->cli->paddedItem('scan','Scan repository dir for new packages and components');
					echo $this->cli->paddedSubItem('[--install]','Add and install all found components');
					echo PHP_EOL;
					echo $this->cli->paddedItem('add [package:key]','Add a component to the registry');
					echo $this->cli->paddedSubItem('[--install]','Run installer once added');
					echo PHP_EOL;
					echo $this->cli->paddedItem('add-package [key]','Add and install all components from a package');
					echo $this->cli->paddedSubItem('[--path=<path>]','Use a path instead of a package folder name');
					echo PHP_EOL;
					echo $this->cli->paddedItem('install [key]','Run a component\'s installer');
					echo $this->cli->paddedItem('remove [key]','Remove component from registry');
					echo $this->cli->paddedItem('update','Update all existing component configurations from their package.ini');
					echo $this->cli->paddedSubItem('[--cleanup]','Remove unused dependency packages');
					break;

				default:
					echo $this->cli->string('No help available about this topic :(','red');
			}

		}
	}

	/**
	 * system check and tests
	 * @param \Base $f3
	 * @param $params
	 */
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

	function setup(\Base $f3, $params) {
		$setup = Setup::instance();
		echo $this->cli->string('Running install...',null,null,'bold').PHP_EOL;
		sleep(1);
		$results = $setup->install_base();
		foreach ($results as $log) {
			echo $this->cli->string($log).PHP_EOL;
			usleep(50*1000);
		}
		echo $this->cli->string('Done','green',null,'bold,invert');
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