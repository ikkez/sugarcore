<?php

namespace Sugar\Utility;

class UI extends \Prefab {

	protected $fw;
	
	function __construct() {
		$this->fw = \Base::instance();
	}

	/**
	 * trim absolute base path to make it compatible with relative document base
	 * @param $val
	 * @return string
	 */
	public function baseTrim($val) {
		return ltrim($val,'/');
	}

	/**
	 * add existing query parameters to an URI
	 * @param string $path
	 * @return string
	 */
	public function addQueryString($path) {
		$url = parse_url($path);
		parse_str(isset($url['query']) ? $url['query'] : '',$query);
		$search = $this->fw->get('GET');
		$query = $query + $search;
		$query_string = http_build_query($query);
		return $url['path'].($query_string?'?'.$query_string:'');
	}

	/**
	 * get the first existing UI path for an asset
	 * @param $val
	 * @param bool $strict
	 * @return mixed
	 */
	public function uiPath($val,$strict=false) {
		foreach($this->fw->split($this->fw->get('UI')) as $path)
			if (file_exists($path.$val))
				return $path.$val;
		return $strict ? FALSE : $val;
	}

	/**
	 * custom alias + base trim filter
	 * @param $name
	 * @param array $args
	 * @return string
	 */
	public function alias($name,$args=array()) {
		if (isset($this->fw->{'ALIASES.'.$name}))
			return ltrim($this->fw->alias($name,$args),'/');
		else {
			// alias not found
			return '';
		}
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