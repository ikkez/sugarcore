<?php

namespace Component\File;


use Sugar\Component;
use Sugar\Flow;

class Reader extends Component {

	use Flow;

	protected $data;
	protected $error=[];

	function read($fileName) {
		if (!file_exists($fileName)) {
			$this->error[]=sprintf('File does not exist: "%s"',$fileName);
		} else
			$this->data = \Base::instance()->read($fileName);
		var_dump('read:'.$fileName);
	}

	function data() {
		var_dump('reader.out');
		return $this->data;
	}

	function error() {
		return $this->error;
	}

	function flow() {
		// config here
		$this->in('source', ['datatype' => \Sugar\Flow\Port::DT_String]);
		$this->out('out', ['datatype' => \Sugar\Flow\Port::DT_String]);
		$this->out('error');

		//		$this->on('in.source.data',Reader::class.'->read');
		$this->on('in.source',[$this,'read']);
		$this->on('out.error',[$this,'error']);
		$this->on('out.out',[$this,'data']);
	}
}