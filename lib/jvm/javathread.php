<?php

class JavaThread {
	private $name;
	private $instance;
	private $exceptions;

	public function __construct($name, &$instance) {
		$this->name = $name;
		$this->instance = $instance;
		$this->exceptions = array();
	}

	public function throwexception($exception) {
		array_push(&$exceptions, $exception);
	}
}
