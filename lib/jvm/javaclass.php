<?php

class JavaClassInstance {
	private $staticclass;

	private $nativemethods;
	private $variables;

	public function __construct(&$staticclass) {
		$this->staticclass = $staticclass;
		$this->variables = array();
		$this->callMethod('<init>', '()V');
	}

	public function callMethod($name, $signature, $args = NULL) {
		$methodId = $this->staticclass->getMethodId($name, $signature);
	}
}
