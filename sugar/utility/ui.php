<?php

namespace Sugar\Utility;

class UI {

	/**
	 * trim absolute base path to make it compatible with relative document base
	 * @param $val
	 * @return string
	 */
	static public function baseTrim($val) {
		return ltrim($val,'/');
	}

	/**
	 * add existing query parameters to an URI
	 * @param string $path
	 * @return string
	 */
	static public function addQueryString($path) {
		$url = parse_url($path);
		parse_str(isset($url['query']) ? $url['query'] : '',$query);
		$search = \Base::instance()->get('GET');
		$query = $query + $search;
		$query_string = http_build_query($query);
		return $url['path'].($query_string?'?'.$query_string:'');
	}

	/**
	 * get the first existing UI path for an asset
	 * @param $val
	 * @return mixed
	 */
	static public function uiPath($val) {
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		foreach($f3->split($f3->get('UI')) as $path)
			if (file_exists($path.$val))
				return $path.$val;
		return $val;
	}

	/**
	 * custom alias + base trim filter
	 * @param $name
	 * @param array $args
	 * @return string
	 */
	static public function alias($name,$args=array()) {
		return ltrim(\Base::instance()->alias($name,$args),'/');
	}

	/**
	 * return current render stats
	 * @return string
	 */
	static function rendertime() {
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		return $f3->format('Page rendered in {0} ms / Memory usage {1} MB',
			round(1e3*(microtime(TRUE)-$f3->TIME),2),
			round(memory_get_usage(TRUE)/1e6,1));
	}
}