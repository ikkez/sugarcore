<?php

namespace Sugar\Storage;

interface KeyValueInterface {

	/**
	 * get value by key from storage
	 * @param $key
	 * @return mixed|false
	 */
	function load($key);

	/**
	 * set a value to a key
	 * @param $key
	 * @param $val
	 * @return mixed
	 */
	function save($key,$val);

	/**
	 * remove key from storage
	 * @param $key
	 * @return mixed
	 */
	function delete($key);

	/**
	 * return all data from storage
	 * @return array
	 */
	function getAll();

}