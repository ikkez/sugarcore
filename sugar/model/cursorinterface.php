<?php

namespace Sugar\Model;

interface CursorInterface extends \IteratorAggregate {

	/**
	 *	Return database type
	 *	@return string
	 **/
	function dbtype();

	/**
	 *	Return field names
	 *	@return array
	 **/
	function fields();

	/**
	 *	Return fields of mapper object as an associative array
	 *	@return array
	 **/
	function cast();

	/**
	 *	Return records (array of mapper objects) that match criteria
	 *	@return array
	 **/
	function find();

	/**
	 *	Count records that match criteria
	 *	@return int
	 **/
	function count();

	/**
	 *	Insert new record
	 *	@return array
	 **/
	function insert();

	/**
	 *	Update current record
	 *	@return array
	 **/
	function update();

	/**
	 *	Get cursor's equivalent external iterator
	 *	Causes a fatal error in PHP 5.3.5 if uncommented
	 *	return ArrayIterator
	 **/
	function getiterator();

	/**
	 *	Return TRUE if current cursor position is not mapped to any record
	 *	@return bool
	 **/
	function dry();

	/**
	 *	Return first record (mapper object) that matches criteria
	 *	@return static|FALSE
	 **/
	function findone();

	/**
	 *	Return array containing subset of records matching criteria,
	 *	total number of records in superset, specified limit, number of
	 *	subsets available, and actual subset position
	 *	@return array
	 **/
	function paginate();

	/**
	 *	Map to first record that matches criteria
	 *	@return array|FALSE
	 **/
	function load() ;

	/**
	 *	Return the count of records loaded
	 *	@return int
	 **/
	function loaded();

	/**
	 *	Map to first record in cursor
	 *	@return mixed
	 **/
	function first();

	/**
	 *	Map to last record in cursor
	 *	@return mixed
	 **/
	function last();

	/**
	 *	Map to nth record relative to current cursor position
	 *	@return mixed
	 **/
	function skip();

	/**
	 *	Map next record
	 *	@return mixed
	 **/
	function next();

	/**
	 *	Map previous record
	 *	@return mixed
	 **/
	function prev();

	/**
	 * Return whether current iterator position is valid.
	 *	@return bool
	 */
	function valid();

	/**
	 *	Save mapped record
	 *	@return mixed
	 **/
	function save();

	/**
	 *	Delete current record
	 *	@return int|bool
	 **/
	function erase();

	/**
	 *	Reset cursor
	 *	@return NULL
	 **/
	function reset();

}