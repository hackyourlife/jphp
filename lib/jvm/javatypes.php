<?php

define('JAVA_T_BOOLEAN',			(int) 4);
define('JAVA_T_CHAR',				(int) 5);
define('JAVA_T_FLOAT',				(int) 6);
define('JAVA_T_DOUBLE',				(int) 7);
define('JAVA_T_BYTE',				(int) 8);
define('JAVA_T_SHORT',				(int) 9);
define('JAVA_T_INT',				(int)10);
define('JAVA_T_LONG',				(int)11);

class JavaArray {
	private $array = array();
	public $length;
	public $type;
	public function __construct($length, $type) {
		$this->length = $length;
		$this->type = $type;
	}
	public function get($index) {
		if(!isset($this->array[$index])) {
			return NULL;
		}
		return $this->array[$index];
	}
	public function set($index, $value) {
		$this->array[$index] = $value;
	}
}
