<?php
/**
 *  Sugarcore - F3 Application Platform
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

// preflight system check
if ((float)PCRE_VERSION<8.0)
	trigger_error('PCRE version is out of date');

if (PHP_VERSION_ID < 50407)
	die('You need at least PHP 5.4.7');

if ($ext_ready = file_exists(EXT_LIB.'src/autoload.php'))
	require_once(EXT_LIB.'src/autoload.php');
require_once('vendor/autoload.php');

/** @var \Base $f3 */
$f3 = \Base::instance();

// init core config
$f3->config(__DIR__.'/config.ini');
if (file_exists($config_ext=$f3->get('CORE.data_path').'config.ini'))
	$f3->config($config_ext);
\Sugar\Config::instance();

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