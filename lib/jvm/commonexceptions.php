<?php

class NullPointerException extends Exception {
	public function __construct($msg = NULL) {
		if($msg !== NULL) {
			parent::__construct($null);
		} else {
			parent::__construct();
		}
	}
}

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

class NoSuchReferenceException extends Exception {
	public function __construct($name) {
		if(is_string($name)) {
			parent::__construct("Reference not found: $name");
		} else {
			parent::__construct("Reference not found");
		}
	}
}

function printException($e) {
	print("[ERROR] {$e->getFile()}:{$e->getLine()}: {$e->getMessage()}\n");
	print($e->getTraceAsString());
}
