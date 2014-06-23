<?php

define('JAVA_VARIABLE_NULL', 0);
class JavaVariable {
	private $type;
	private $content;

	public function __construct($type, $content = NULL) {
		$this->type = $type;
		$this->content = $content;
	}

	public function getType() {
		return $this->type;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}
}
