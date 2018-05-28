<?php
/**
 * Storage - manages the DB Object creation
 *
 * The contents of this file are subject to the terms of the GNU General
 * Public License Version 3.0. You may not use this file except in
 * compliance with the license. Any of the license terms and conditions
 * can be waived if you get permission from the copyright holder.
 *
 * Copyright (c) 2018 ~ ikkez
 * Christian Knuth <ikkez0n3@gmail.com>
 *
 * @version 0.5.0
 * @date: 26.03.2018
 * @since: 29.04.2012
 **/

namespace Sugar;

class Storage extends \Prefab {

	private
		$dsnTypes=array(
		'JIG',
		'MONGO',
		'SQLITE',
		'MYSQL',
		'PGSQL',
		'SQLSRV',
		// TODO: add those
//		'sybase',
//		'dblib',
//		'odbc',
//		'oci',
	);

	const
		ERROR_UnknownTypeOfDSN='DB of DSN type "%s" is not supported',
		ERROR_NoNameDefined='You have to specify a name for the DB',
		ERROR_DBNotLoaded='DB with name "%s" could not be loaded',
		ERROR_ConfigNotFound='No Configuration found for DB "%s"',

		ERROR_FileDB_DataPathMissing='You need to specify a path for Jig FileDB',
		ERROR_FileDB_FormatMissing='You need to specify a file format for Jig FileDB',
		ERROR_FileDB_UnknownFormat='Unknown file format selected for Jig DB',

		ERROR_SQL_NoHostDefined='You have to specify a host for "%s" DSN',
		ERROR_SQL_NoDBDefined='You have to specify a DB for "%s" DSN',
		ERROR_SQL_NoUserDefined='No Username was specified for "%s" DSN',
		ERROR_SQL_NoPasswordDefined='Password missing for "%s" DSN',

		ERROR_SQlite_DataPathMissing='You need to specify conf.dataPath for SQlite';


	/**
	 * @param array|string $conf config name or conf array
	 * @param null|string $name name for this db connection
	 * @return mixed
	 */
	public function load($conf,$name=NULL) {
		// if string is provided, load config
		if (is_string($conf)) {
			if (!$name) $name=$conf;
			$conf=Config::instance()->DB[$name];
			if (!$conf)
				trigger_error(sprintf(self::ERROR_ConfigNotFound,$name),E_USER_ERROR);
		};

		if (!$name)
			trigger_error(self::ERROR_NoNameDefined,E_USER_ERROR);

		if (\Registry::exists('DB_'.$name))
			return $this->get($name);

		$type=strtoupper($conf['type']);

		if (!in_array($type,$this->dsnTypes))
			trigger_error(sprintf(self::ERROR_UnknownTypeOfDSN,$conf['type']),
			E_USER_ERROR);

		switch ($type) {
			case 'JIG':

				if (!array_key_exists('dir',$conf))
					trigger_error(self::ERROR_FileDB_DataPathMissing,E_USER_ERROR);

				$formatTypes=array(
					'serialized'=>\DB\Jig::FORMAT_Serialized,
					'json'=>\DB\Jig::FORMAT_JSON,
				);

				if (!array_key_exists('format',$conf))
					trigger_error(self::ERROR_FileDB_FormatMissing,E_USER_ERROR);
				elseif (!array_key_exists($conf['format'],$formatTypes))
					trigger_error(self::ERROR_FileDB_UnknownFormat,E_USER_ERROR);

				$lazy = array_key_exists('lazy',$conf) ? (bool) $conf['lazy'] : false;

				$db=new \DB\Jig($conf['dir'],$formatTypes[$conf['format']],$lazy);
				break;

			case 'MYSQL':
				if (!array_key_exists('host',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoHostDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('dbname',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoDBDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('user',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoUserDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('password',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoPasswordDefined,$name),
						E_USER_ERROR);

				$db=new \DB\SQL('mysql:host='.$conf['host'].
					';port='.$conf['port'].';dbname='.$conf['dbname'],
					$conf['user'],$conf['password']);
				break;

			case 'PGSQL':
				if (!array_key_exists('host',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoHostDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('dbname',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoDBDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('user',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoUserDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('password',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoPasswordDefined,$name),
						E_USER_ERROR);

				$db=new \DB\SQL('pgsql:host='.$conf['host'].';dbname='.$conf['dbname'],
					$conf['user'],$conf['password']);
				break;

			case 'SQLSRV':
				if (!array_key_exists('host',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoHostDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('dbname',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoDBDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('user',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoUserDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('password',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoPasswordDefined,$name),
						E_USER_ERROR);

				$db=new \DB\SQL('sqlsrv:SERVER='.$conf['host'].';Database='.$conf['dbname'],
					$conf['user'],$conf['password']);
				break;

			case 'SQLITE':
				if (!array_key_exists('path',$conf))
					trigger_error(self::ERROR_SQlite_DataPathMissing,E_USER_ERROR);
				$db=new \DB\SQL('sqlite:'.$conf['path']);
				break;

			case 'MONGO':
				if (!array_key_exists('host',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoHostDefined,$name),
						E_USER_ERROR);
				if (!array_key_exists('name',$conf))
					trigger_error(sprintf(self::ERROR_SQL_NoDBDefined,$name),
						E_USER_ERROR);

				$db=new \DB\Mongo('mongodb://'.$conf['host'].':'.
					$conf['port'],$conf['dbname']);
				break;
		}
		return isset($db)?\Registry::set('DB_'.$name,$db):FALSE;
	}


	/**
	 * returns a DB object
	 * @param $name
	 * @return mixed
	 */
	public function get($name) {
		if (!\Registry::exists('DB_'.$name)) {
			$db=$this->load($name);
			if (!$db)
				trigger_error(sprintf(self::ERROR_DBNotLoaded,$name),E_USER_ERROR);
			return $db;
		}
		return \Registry::get('DB_'.$name);
	}

	/**
	 * unloads a DB
	 * @param $name
	 */
	public function unload($name) {
		\Registry::clear('DB_'.$name);
	}

	/**
	 * save storage configuration to config
	 * @param $name
	 * @param $conf
	 */
	public function save($name,$conf) {
		$cfg = Config::instance();
		$cfg->set('DB_'.$name, $conf);
		$cfg->save();
	}
}