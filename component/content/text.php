<?php

namespace Component\Content;


use Sugar\Component;
use Sugar\Flow;

class Text extends Component {

	use Flow;

	protected $data;

	function setText($value) {
		$this->data=$value;
	}

	function getText() {
		return $this->data;
	}

	function flow() {
		// Register ports
		//		$this->ports['in']['text'] = ['datatype' => 'all'];
		//		$this->ports['out']['text'] = ['datatype' => 'all'];

		$this->in('text', ['type'=>'string']);
		$this->out('render', ['type'=>'string']);

		$this->on('in.text.data',[$this,'in']);
		$this->on('out.text.data',[$this,'out']);
	}
}