<?php

namespace Sugar\Storage;

interface KeyValueInterface {

	function getOne($val);

	function saveOne($data,$val);

	function getAll();

}