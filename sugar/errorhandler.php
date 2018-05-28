<?php

/**
 * Sugarcore Error Handler
 *
 */

namespace Sugar;

class ErrorHandler {

	function render() {

		// if the error happened within a template,
		// we need to clear everything up first
		while(ob_get_level())
			ob_end_clean();

		$f3 = \Base::instance();
		$f3->set('ESCAPE', false);

		if($f3->get('AJAX')) {
			die(json_encode(array('error'=>$f3->get('ERROR.text'))));
		}

//		if ($f3->get('ERROR.code') == 500) {
			$f3->set('HIGHLIGHT',false);

			$trace = $f3->get('ERROR.trace');
			$f3->set('DEBUG',2);
			$full_trace = $f3->trace();
			$msg = $f3->VERB.': '.$f3->REALM."\n"."\n".
				'Error: '.$f3->get('ERROR.text')."\n"."\n".
				'Stack:'."\n\n".$full_trace."\n"."\n"."\n";

			$msg.='IP: '.$f3->ip()."\n\n";

//		}


		header("Content-Type: text");
		echo  $msg;
	}

}