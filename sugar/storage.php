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
 * @version 1.0.0
 * @date: 18.09.2018
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
		E_UnknownTypeOfDSN='DB of DSN type "%s" is not supported',
		E_NoNameDefined='You have to specify a name for the DB',
		E_DBNotLoaded='DB with name "%s" could not be loaded',
		E_ConfigNotFound='No Configuration found for DB "%s"',

		E_FileDB_DataPathMissing='You need to specify a path for Jig FileDB',
		E_FileDB_FormatMissing='You need to specify a file format for Jig FileDB',
		E_FileDB_UnknownFormat='Unknown file format selected for Jig DB',

		E_SQL_NoHostDefined='You have to specify a host for "%s" DSN',
		E_SQL_NoDBDefined='You have to specify a DB for "%s" DSN',
		E_SQL_NoUserDefined='No Username was specified for "%s" DSN',
		E_SQL_NoPasswordDefined='Password missing for "%s" DSN',

		E_SQlite_DataPathMissing='You need to specify conf.dataPath for SQlite';


	/**
	 * @param array|string $conf config name or conf array
	 * @param null|string $name name for this db connection
	 * @return mixed
	 */
	public function load($conf,$name=NULL) {
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		
		// if string is provided, load config
		if (is_string($conf)) {
			if (!$name) $name=$conf;
			$conf=Config::instance()->DB[$name];
			if (!$conf)
				$f3->error(500,sprintf(self::E_ConfigNotFound,$name));
		};

		if (!$name)
			$f3->error(500,self::E_NoNameDefined);

		if (\Registry::exists('DB_'.$name))
			return $this->get($name);

		$type=strtoupper($conf['type']);

		if (!in_array($type,$this->dsnTypes))
			$f3->error(500,sprintf(self::E_UnknownTypeOfDSN,$conf['type']));

		try {
			switch ($type) {
				case 'JIG':

					if (!array_key_exists('dir',$conf))
						$f3->error(500,self::E_FileDB_DataPathMissing);

					$formatTypes=array(
						'serialized'=>\DB\Jig::FORMAT_Serialized,
						'json'=>\DB\Jig::FORMAT_JSON,
					);

					if (!array_key_exists('format',$conf))
						$f3->error(500,self::E_FileDB_FormatMissing);
					elseif (!array_key_exists($conf['format'],$formatTypes))
						$f3->error(500,self::E_FileDB_UnknownFormat);

					$lazy = array_key_exists('lazy',$conf) ? (bool) $conf['lazy'] : false;

					$db=new \DB\Jig($conf['dir'],$formatTypes[$conf['format']],$lazy);
					break;

				case 'MYSQL':
					if (!array_key_exists('host',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoHostDefined,$name));
					if (!array_key_exists('dbname',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoDBDefined,$name));
					if (!array_key_exists('username',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoUserDefined,$name));
					if (!array_key_exists('password',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoPasswordDefined,$name));

					$db=new \DB\SQL('mysql:host='.$conf['host'].
						';port='.$conf['port'].';dbname='.$conf['dbname'],
						$conf['username'],$conf['password'],[
							\PDO::ATTR_TIMEOUT => isset($conf['timeout'])?$conf['timeout']:20,
							\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
						]);
					break;

				case 'PGSQL':
					if (!array_key_exists('host',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoHostDefined,$name));
					if (!array_key_exists('dbname',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoDBDefined,$name));
					if (!array_key_exists('username',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoUserDefined,$name));
					if (!array_key_exists('password',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoPasswordDefined,$name));

					$db=new \DB\SQL('pgsql:host='.$conf['host'].';dbname='.$conf['dbname'],
						$conf['username'],$conf['password'],[
							\PDO::ATTR_TIMEOUT => isset($conf['timeout'])?$conf['timeout']:30,
							\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
						]);
					break;

				case 'SQLSRV':
					if (!array_key_exists('host',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoHostDefined,$name));
					if (!array_key_exists('dbname',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoDBDefined,$name));
					if (!array_key_exists('username',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoUserDefined,$name));
					if (!array_key_exists('password',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoPasswordDefined,$name));

					$db=new \DB\SQL('sqlsrv:SERVER='.$conf['host'].';Database='.$conf['dbname'].';'.
						'LoginTimeout='.(isset($conf['timeout'])?$conf['timeout']:30),
						$conf['username'],$conf['password']);
					break;

				case 'SQLITE':
					if (!array_key_exists('path',$conf))
						$f3->error(500,self::E_SQlite_DataPathMissing);
					$db=new \DB\SQL('sqlite:'.$conf['path']);
					break;

				case 'MONGO':
					if (!array_key_exists('host',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoHostDefined,$name));
					if (!array_key_exists('name',$conf))
						$f3->error(500,sprintf(self::E_SQL_NoDBDefined,$name));

					$db=new \DB\Mongo('mongodb://'.$conf['host'].':'.
						$conf['port'],$conf['dbname']);
					break;
			}


		} catch (\Exception $e) {
			echo $e->getMessage();
			exit();
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
				\Base::instance()->error(500,sprintf(self::E_DBNotLoaded,$name));
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