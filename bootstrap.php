<?php

// system check
if ((float)PCRE_VERSION<8.0)
	trigger_error('PCRE version is out of date');

if (PHP_VERSION_ID < 50407)
	die('You need at least PHP 5.4.7');

// composer autoloader for required packages and dependencies
require __DIR__.'/../vendor/autoload.php';

/** @var \Base $f3 */
$f3 = \Base::instance();

ini_set('display_errors', 1);
error_reporting(-1);

if (@ini_get('max_execution_time') < 180)
	@ini_set('max_execution_time',180);

$f3->BITMASK = ENT_COMPAT|ENT_SUBSTITUTE;

// normalize ROOT
$suffix = str_replace($f3->ROOT,'',getcwd());
if (!empty($f3->BASE)) {
	$pos = strpos($f3->fixslashes($suffix),$f3->fixslashes($f3->BASE));
	if ($pos !== FALSE)
		$suffix=substr($suffix,0,-strlen($f3->BASE));
}
if (!empty($suffix)) {
	var_dump($suffix);
	$f3->concat('ROOT',$suffix);
	$f3->concat('SERVER.DOCUMENT_ROOT',$suffix);
}

// init core config
$f3->config('inc/config.ini');
\Sugar\Config::instance();

// preflight
if (!is_dir($f3->get('TEMP')) || !is_writable($f3->get('TEMP')))
	$preErr[] = sprintf('please make sure that the \'%s\' directory is existing and writable.',$f3->get('TEMP'));
if (!is_writable('inc/data/'))
	$preErr[] = sprintf('please make sure that the \'%s\' directory is writable.','inc/data/');
if (!is_writable('app/'))
	$preErr[] = sprintf('please make sure that the \'%s\' directory is writable.','app/');

if (isset($preErr)) {
	header('Content-Type: text;');
	die(implode("\n",$preErr));
}

// init Dependency Injection Container
\Registry::set('DICE', $dice = new \Dice\Dice());
$f3->set('CONTAINER', function($class,$args=null) use ($dice) {
	if (is_a($class,'Sugar\Component', true)) {
		return \Sugar\Service\Registry::instance()->create($class);
	} else
		return $dice->create($class,$args?:[]);
});

//$f3->route('GET /vue', function($f3){
//	$f3->ONERROR = NULL;
//	$f3->AUTOLOAD = 'app/admin/,ext/,inc/';
//	$f3->UI = 'app/admin/ui/';
//	$view = new \View\DynamicVue();
//	$view->setVueFile('templates/content/user/user.vue');
//	$view->render();
//});
//


// init front controller
$app = new \Sugar\Service\App();
$key = $app->select();
$app->load($key);
$app->run();