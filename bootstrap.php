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

require EXT_LIB."src/autoload.php";
require "vendor/autoload.php";

/** @var \Base $f3 */
$f3 = \Base::instance();

ini_set('display_errors', 1);
error_reporting(-1);

$f3->BITMASK = ENT_COMPAT|ENT_SUBSTITUTE;

// normalize ROOT
$cwd=getcwd();
if (strlen($f3->ROOT)<$cwd) {
	$suffix = str_replace($f3->ROOT,'',$cwd);
	if (!empty($f3->BASE)) {
		$pos = strpos($f3->fixslashes($suffix),$f3->fixslashes($f3->BASE));
		if ($pos !== FALSE)
			$suffix=substr($suffix,0,-strlen($f3->BASE));
	}
	if (!empty($suffix)) {
		$f3->concat('ROOT',$suffix);
		$f3->concat('SERVER.DOCUMENT_ROOT',$suffix);
	}
}

// init core config
$f3->config(__DIR__.'/config.ini');
if (file_exists($config_ext=$f3->get('CORE.data_path').'config.ini'))
	$f3->config($config_ext);

$config = \Sugar\Config::instance();

// system check & base install
$log=[];
if (!$config->exists('install') || $config->install) {
	$log[]='<p>Running setup.</p>';
	\Sugar\Service\Setup::instance()->install_base();
	$log[]='<p>Done.</p>';
}
if (!$config->exists('preflight') || $config->preflight) {
	$log[]='<p>Running system preflight tests</p>';
	$preRes = \Sugar\Service\Setup::instance()->preflight();
	$preErr=[];
	foreach ($preRes as $test) {
		if ($test['status'] === FALSE) {
			$preErr[] = '<span class="red">FAILED</span>: '.$test['text'];
		}
	}
	if (empty($preErr) || $f3->exists('GET.check_preflight')) {
		$config->preflight=FALSE;
		if (!$config->exists('install'))
			$config->install=FALSE;
		$config->save();
		$f3->reroute($f3->PATH);
	} else {
		$log[]="<strong>Problems found:</strong>";
		$log[]='<ul><li>'.implode("</li><li>",$preErr).'</li></ul>';
		$log[]='<p>Please fix problems above.<br> <a href="?">test again</a> or <a href="?check_preflight"> skip (I know what I\'m doing)</a></p>';
	}
}
if ($log) {
	header('Content-Type: text/html');
	$body=<<<HTML
	<html><title>Sugarcore Install</title><head><style>*{font-family: Verdana;} code{ background: #e3e3e3; padding: 5px;} pre{padding: 15px; background: #e3e3e3; } pre code{padding: 0;} .red{color:darkred}</style></head><body>%s</body></html>
HTML;
	echo sprintf($body,implode($log));
	die();
}

if (ini_get('max_execution_time') < 60)
	@ini_set('max_execution_time',60);

// init Dependency Injection Container
\Registry::set('DICE', $dice = new \Dice\Dice());
$f3->set('CONTAINER', function($class,$args=null) use ($dice) {
	if (is_a($class,'Sugar\Component', true)) {
		return \Sugar\Service\Registry::instance()->create($class);
	} else
		return $dice->create($class,$args?:[]);
});

// init front controller
$app = new \Sugar\Service\App();
$key = $app->select();
$app->load($key);
$app->run();