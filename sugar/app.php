<?php

namespace Sugar;

abstract class App extends \Sugar\Component {

	abstract function load();

	abstract function run();

}