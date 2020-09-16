<?php


namespace Sugar\Utility;


class Config extends \Prefab {

	/**
	 * scoped configuration file parser
	 * @param $source
	 * @param string|null $prefix
	 */
	function parse($source, $prefix=NULL) {
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		if (is_string($source))
			$source=$f3->split($source);
		foreach ($source as $file) {
			preg_match_all(
				'/(?<=^|\n)(?:'.
				'\[(?<section>.+?)\]|'.
				'(?<lval>[^\h\r\n;].*?)\h*=\h*'.
				'(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
				')(?=\r?\n|$)/',
				$f3->read($file),
				$matches,PREG_SET_ORDER);
			if ($matches) {
				$sec='globals';
				foreach ($matches as $match) {
					if ($match['section']) {
						$sec=$match['section'];
						if (preg_match(
								'/^(?!(?:global)s\b)'.
								'(.*?)(?:\s*[:>])/i',$sec,$msec) &&
							!$f3->exists($prefix.$msec[1]))
							$f3->set($prefix.$msec[1],NULL);
						continue;
					}
					$rval=preg_replace(
						'/\\\\\h*(\r?\n)/','\1',$match['rval']);
					$ttl=NULL;
					if (preg_match('/^(.+)\|\h*(\d+)$/',$rval,$tmp)) {
						array_shift($tmp);
						list($rval,$ttl)=$tmp;
					}
					$args=array_map(
						function($val) use ($f3) {
							$val=$f3->cast($val);
							if (is_string($val))
								$val=strlen($val)?
									preg_replace('/\\\\"/','"',$val):
									NULL;
							return $val;
						},
						// Mark quoted strings with 0x00 whitespace
						str_getcsv(preg_replace(
							'/(?<!\\\\)(")(.*?)\1/',
							"\\1\x00\\2\\1",trim($rval)))
					);
					preg_match('/^(?<section>[^:]+)/',
						$sec,$parts);
					$custom=(strtolower($parts['section'])!='globals');
					if (count($args)>1)
						$args=[$args];
					if (isset($ttl))
						$args=array_merge($args,[$ttl]);
					call_user_func_array(
						[$f3,'set'],
						array_merge(
							[
								$prefix.($custom?($parts['section'].'.'):'').
								$match['lval']
							],
							$args
						)
					);

				}
			}
		}
	}
}
