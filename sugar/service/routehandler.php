<?php

namespace Sugar\Service;

/**
 *
 * Route wrapper to give the F3 routing some more Dependency Injection capabilities
 *
 * thanks for idea to: richardgoldstein
 */
class RouteHandler {

	protected $f3;

	protected $dice;

	protected $routes_key;

	/**
	 * RouteHandler constructor.
	 * @param string $routes_key
	 */
	public function __construct($routes_key = 'APP.ROUTES') {
		$this->f3 = \Base::instance();
		$this->dice = \Registry::get('DICE');
		$this->routes_key = $routes_key;
	}

	/**
	 * go through all registered routes
	 * @param bool $scoped
	 */
	function prepare() {
		if ($this->f3->exists($this->routes_key,$routes)) {
			foreach ($routes?:[] as $pattern => $handler) {
				if (is_array($handler)) {
					// sync / ajax keys
					if (!is_numeric(key($handler))) {
						$pattern.= ' ['.key($handler).']';
						$handler = [current($handler)];
					}
					$this->route($pattern,...$handler);
				}
				else
					$this->route($pattern,$handler);
			}
		}
	}

	/**
	 * Replaces the F3 route handler for routes here, enabling Dice integration
	 * and dependency injection in the controllers
	 *
	 * @param $pattern
	 * @param $handler
	 * @param int $ttl
	 * @param int $kbps
	 * @param bool $scoped
	 */
	public function route($pattern,$handler,$ttl=0,$kbps=0) {
		$prepend = $this->f3->get('APP.ROUTE');
		if ($prepend)
			$pattern = preg_replace([
				'/(\/.+$)/',
				'/(\/$)/',
			],[
				'/'.$prepend.'$1',
				'/'.$prepend,
			],$pattern,1);
		// instead of passing the callable directly to $f3, create a stub function
		// which allows to get the controller class from the container
		if (is_string($handler)) {
			$this->f3->route($pattern, function($f3,$args) use ($handler) {
					\Event::instance()->emit('route.call','route: '.$f3->PATH.' > '.((string)$handler));
					return $f3->call(
						$this->grab($handler,$args)
						,[$f3,$args],'beforeroute,afterroute');
				},$ttl,$kbps);
		} else {
			// If its not string, just pass it on the the F3 handler
			$this->f3->route($pattern,$handler,$ttl,$kbps);
		}
	}

	/**
	 *	modified grab method
	 * @param $func
	 * @param null $args
	 * @return array
	 */
	private function grab($func,$args=NULL) {
		if (preg_match('/(.+)\h*(->|\.|::)\h*(.+)/s',$func,$parts)) {
			// convert component name to class and register DIC rules
			if ($parts[2]=='.') {
				if ($parts[1][0]=='@') {
					$dyn=substr($parts[1],1);
					if (isset($args[$dyn])) {
						$parts[1]=$args[$dyn];
				}}
//				if ($parts[3][0]=='@') {
//					$dyn=substr($parts[3],1);
//					if (isset($args[$dyn])) {
//						$parts[3]=$args[$dyn];
//				}}
				$reg = \Sugar\Service\Registry::instance();
				$conf = $reg->load($parts[1]);
				$reg->mapClassesToComponents([
					$conf['class'] => $parts[1]
				]);
				$parts[1] = $conf['class'];
				$parts[2] = '->';
			}
			unset($parts[0]);
			$func = implode($parts);
		}
		return $this->f3->grab($func,$args);
	}
}