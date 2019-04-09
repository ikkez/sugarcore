<?php

/**
 * Sugarcore Error Handler
 */

namespace Sugar;

use Sugar\Utility\CLI;

class ErrorHandler {

	function render($f3,$params) {

		// if the error happened within a template,
		// we need to clear everything up first
		while(ob_get_level())
			ob_end_clean();

		if ($f3->CLI) {
			$cli = CLI::instance();
			echo $cli->errorString($f3->get('ERROR.text'),$f3->get('ERROR.code'));
			echo PHP_EOL;
			if ($f3->get('ERROR.code') == 500)
				echo $f3->get('ERROR.trace');
			echo PHP_EOL;
			exit();
		}

		if ($f3->get('AJAX')) {
			header("Content-Type: application/json");
			die(json_encode(array('error'=>$f3->get('ERROR.text'))));
		}

		$f3->set('HIGHLIGHT',false);

		$trace = $f3->get('ERROR.trace');
		$reason = $f3->status($f3->get('ERROR.code'));
		$msg = $reason.' ['.$f3->get('ERROR.code').']'."\n";
		$msg.= "==================================="."\n"."\n";
		$msg.= $f3->VERB.': '.$f3->REALM."\n"."\n".
			'Error: '.$f3->get('ERROR.text')."\n"."\n".
			'Stack:'."\n\n".$trace."\n"."\n";

		header("Content-Type: text");
		echo $msg;
	}

}