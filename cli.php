<?php

// preflight system check
if ((float)PCRE_VERSION<8.0)
	trigger_error('PCRE version is out of date');

if (PHP_VERSION_ID < 50407)
	die('You need at least PHP 5.4.7');

require_once('vendor/autoload.php');

/** @var \Base $f3 */
$f3 = \Base::instance();

$cli_version = '0.8.2';

// init config
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

// init DIC
\Registry::set('DICE', $dice = new \Dice\Dice());
$f3->set('CONTAINER', function($class,$args=null) use ($dice) {
	return $dice->create($class,$args?:[]);
});

$f3->route('GET @index: / [cli]','Sugar\Component\CLI->index');
$f3->route([
	'GET /@action [cli]',
	'GET /@action/@arg1 [cli]',
	'GET /@action/@arg1/@arg2 [cli]',
	'GET /@action/@arg1/@arg2/@arg3 [cli]',
],'Sugar\Component\CLI->@action');

$f3->run();