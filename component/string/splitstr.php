<?php

namespace Component\String;


use Sugar\Component;
use Sugar\Flow;

class SplitStr extends Component {

	use Flow;

	protected $data;

	function flow() {
		$this->in('set', ['datatype'=> Flow\Port::DT_String]);
		$this->out('get', ['datatype'=>Flow\Port::DT_Array]);

		$this->on('in.set',[$this,'set']);
		$this->on('out.get',[$this,'get']);
	}

	function set($data) {
		$this->data = explode("\n",$data);
		var_dump($this->data);
		var_dump("hier");
	}

	function get() {
		return $this->data;
	}
}