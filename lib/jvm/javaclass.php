<?php

class JavaClassInstance {
	private $staticclass;

	private $nativemethods;
	private $variables;
	private $staticvars;

	public function __construct(&$staticclass) {
		$this->staticclass = $staticclass;
		$this->variables = array();
		$this->callMethod('<init>');
	}

	public function callMethod($name, $args = NULL) {
	}
}
