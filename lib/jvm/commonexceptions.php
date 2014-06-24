<?php

class ClassNotFoundException extends Exception {
	public function __construct($name) {
		parent::__construct("Class not found: $name");
	}
}

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

class NoSuchFieldException extends Exception {
	public function __construct($name) {
		parent::__construct("Field not found: $name");
	}
}
