<?php

class MethodNotFoundException extends Exception {
	public function __construct($name, $signature) {
		parent::__construct("Method not found: $name$signature");
	}
}

class NoCodeSegmentException extends Exception {
	public function __construct() {
		parent::__construct("no code attribute attached to function");
	}
}
