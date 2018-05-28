<?php

namespace Sugar\Flow;

use Sugar\Component\Registry;

class Graph {

	protected $f3;

	protected $events;

	protected $name;

	protected $connections=[];

	function __construct($appName) {
		$this->name=$appName;
		$this->f3 = \Base::instance();
		$this->events = \Event::instance();
	}

	public function run($cmd,$args=NULL) {
		list($cmp,$portType,$portName) = explode('.',$cmd);
		Registry::instance()->create($cmp);
//		var_dump($cmd);
//		var_dump($args);
//		$this->events->emit($cmd,'composer.json');
//		($this->events->emit($cmp.'.out'));
//		$this->events->on('ReadFile.out.out.SplitbyLines.in.set',function($val){
//			var_dump($val);
//			var_dump('da');
//		});
		($this->events->emit($cmp.'.in','composer.json'));
		($this->events->emit($cmp.'.out'));
	}

	public function build($cmds) {
//		foreach ($args as $key=>$value) {
//
//			if (!is_array($this->connections[$key]))
//				$this->connections[$key]=[];
//
//			$this->connections[$key][]=$value;
//		}
		$this->connections=$cmds;
		var_dump($this->connections);

		foreach ($this->connections as $out => $cmds) {
				var_dump($out);

			foreach ($cmds as $cmd) {
//				$this->registry->create('Template');
				$this->events->on($out,function($val) use ($cmd) {
					list($cmp,$portType,$portName) = explode('.',$cmd);
					$this->events->emit($cmp.'.in',$val);
					$this->events->emit($cmp.'.out',$val);
					var_dump($val);
				});
			}
		}
//		$this->events->on($this->f3->get('CORE.active_app.name').'.'.$key,function($args) use ($value){
//			$this->events->emit($this->f3->get('CORE.active_app.name').'.'.$value,$args);
//		});
//		$this->f3->set($scope.'.'.$key, $value);
	}

	public function parse($file) {
		preg_match_all(
			'/(?<=^|\n)(?:'.
			'\[(?<section>.+?)\h*\|\h*flow\]|'.
			'(?<lval>[^\h\r\n;].*?)\h*>\h*'.
			'(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
			')(?=\r?\n|$)/',
			$this->f3->read($file),
			$matches,PREG_SET_ORDER);
		$cmds=[];
		if ($matches) {
			foreach ($matches as $match) {
				if ($match['section']) {
					$sec=$match['section'];
					if (preg_match('/^((?:\.?\w)+)/i',$sec,$msec) &&
						!$this->f3->exists($msec[0]))
						$this->f3->set($msec[0],NULL);
					continue;
				}
				if (!isset($sec))
					continue;
				$lval=trim($match['lval']);
				$from = explode('.',$lval,2);
				$from = $from[0].'.out.'.$from[1];

				$rval=trim($match['rval']);
				$to = explode('.',$rval,2);
				$to = $to[1].'.in.'.$to[0];

				if (!isset($cmds[$from]))
					$cmds[$from]=array();
				$cmds[$from][]=$to;

			}
//			$this->f3->mset($cmds,$sec.'.');
		}
		return $cmds;
	}
}