<?php

define('JAVA_T_BOOLEAN',			(int) 4);
define('JAVA_T_CHAR',				(int) 5);
define('JAVA_T_FLOAT',				(int) 6);
define('JAVA_T_DOUBLE',				(int) 7);
define('JAVA_T_BYTE',				(int) 8);
define('JAVA_T_SHORT',				(int) 9);
define('JAVA_T_INT',				(int)10);
define('JAVA_T_LONG',				(int)11);

abstract class JavaObject {
	public function finalize() {
	}
	abstract public function getName();
}

class JavaArray extends JavaObject {
	public $array;
	public $length;
	public $type;
	public function __construct($length, $type) {
		$this->length = $length;
		$this->type = $type;
		$this->array = array();
		for($i = 0; $i < $length; $i++) {
			$this->array[$i] = 0;
		}
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
	public function toString() {
		$types = array(
			JAVA_T_BOOLEAN => 'boolean',
			JAVA_T_CHAR => 'char',
			JAVA_T_FLOAT => 'float',
			JAVA_T_DOUBLE => 'double',
			JAVA_T_BYTE => 'byte',
			JAVA_T_SHORT => 'short',
			JAVA_T_INT => 'int',
			JAVA_T_LONG => 'long'
		);
		$type = $this->type;
		if(isset($types[$this->type])) {
			$type = $types[$this->type];
		}
		return $type . '[' . implode(',', $this->array) . ']';
	}
	public function string() {
		$string = '';
		foreach($this->array as $char) {
			$string .= chr($char);
		}
		return $string;
	}
	public function getName() {
		return 'Array';
	}
}
