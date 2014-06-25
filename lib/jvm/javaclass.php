<?php

class JavaClassInstance {
	private $staticclass;

	private $fields;

	public function __construct(&$staticclass) {
		$this->staticclass = $staticclass;
		$this->fields = array();
	}

	public function callMethod($name, $signature, $args = NULL) {
		$method = $this->staticclass->getMethod($name, $signature);
		$native = $this->staticclass->isNative($method);
		if($native) {
			return $this->staticclass->jvm->callNative($this, $name, $signature, $args);
		}
		$interpreter = new Interpreter($this->staticclass->jvm, $this->staticclass->classfile);
		$interpreter->setMethod($method, $args);
		$interpreter->setFields($this->fields);
		$pc = $interpreter->execute();
		$result = $interpreter->getResult();
		$interpreter->cleanup();
		return $result;
	}
}
