<?php

namespace Sugar\Model;

use DB\Cortex;
use Sugar\ComponentTrait;
use Sugar\Service\Registry;
use Sugar\Storage;

abstract class Base extends Cortex implements CursorInterface {

	use ComponentTrait {
		emit as protected component_emit;
		config as protected component_config;
	}

	// datatype shortcuts
	const
		DT_BOOL = 'BOOLEAN',
		DT_BOOLEAN = 'BOOLEAN',
		DT_INT1 = 'INT1',
		DT_TINYINT = 'INT1',
		DT_INT2 = 'INT2',
		DT_SMALLINT = 'INT2',
		DT_INT4 = 'INT4',
		DT_INT = 'INT4',
		DT_INT8 = 'INT8',
		DT_BIGINT = 'INT8',
		DT_FLOAT = 'FLOAT',
		DT_DOUBLE = 'DOUBLE',
		DT_DECIMAL = 'DOUBLE',
		DT_VARCHAR128 = 'VARCHAR128',
		DT_VARCHAR256 = 'VARCHAR256',
		DT_VARCHAR512 = 'VARCHAR512',
		DT_TEXT = 'TEXT',
		DT_LONGTEXT = 'LONGTEXT',
		DT_DATE = 'DATE',
		DT_DATETIME = 'DATETIME',
		DT_TIMESTAMP = 'TIMESTAMP',
		DT_BLOB = 'BLOB',
		DT_BINARY = 'BLOB',
		DT_SERIALIZED = 'SERIALIZED',
		DT_JSON = 'JSON',

		// column default values
		DF_CURRENT_TIMESTAMP = 'CUR_STAMP';

	/**
	 * database object or reference to storage key
	 * @var mixed|string
	 */
	protected $db = 'main';

	/**
	 * prepend string to table name
	 * @var string
	 */
	protected $table_prefix = '';

	/**
	 * apprend string to table name
	 * @var string
	 */
	protected $table_suffix = '';

	/**
	 * enable component behaviour on this model
	 * @var bool
	 */
	protected $is_component = true;

	/**
	 * @var array container for de-/serialization instructions
	 */
	protected $restore = [];

	/**
	 * Base constructor.
	 */
	function __construct() {

		$this->fw = \Base::instance();
		$this->registry = Registry::instance();
		$this->ev = \Event::instance();

		if ($this->is_component) {
			// load as custom defined component
			if ($this->_name != 'Sugar\Model\Base') {
				if ($this->registry->load($this->_name))
					$this->component_config($this->_name);
			} else {
				// load as default component with configuration inheritance
				$class_stack = array_merge(
					[get_called_class()],
					array_values(array_slice(class_parents($this),0,-3)));
				$config = NULL;
				foreach ($class_stack as $class) {
					$config = $this->registry->load($class,$config);
				}
				$this->component_config($class_stack[0],$config);
			}
		}

		if (is_string($this->db)) {
			$this->restore['db_key'] = $this->db;
			$this->db = Storage::instance()->get($this->db);
		}

		$this->table = $this->table_prefix.$this->table.$this->table_suffix;

		parent::__construct();
	}

	function config() {
		trigger_error('Models cannot be instantiated as component directly. Set $_name property instead.',E_USER_ERROR);
	}

	/**
	 * rewire component emitter
	 *
	 * @param $event
	 * @param null $val
	 * @param array $context
	 * @param bool $hold
	 * @return mixed
	 */
	function emit($event, $val=null,&$context=[],$hold=true) {
		$val = parent::emit($event,$val);
		return ($this->ev && $this->is_component)
			? $this->component_emit($event, $val,$context,$hold)
			: $val;
	}

	/**
	 * serialization helper
	 * @return array
	 */
	function __sleep() {
		$primary = NULL;
		if ($this->valid())
			$primary = $this->get($this->primary);
		$this->restore['pkey'] = $primary;
		return array('restore');
	}

	/**
	 * deserialization helper
	 */
	function __wakeup() {
		$this->db = $this->restore['db_key'];
		$this->__construct();
		if ($this->restore['pkey']) {
			$this->load(['_id = ?',$this->restore['pkey']]);
		}
	}

}