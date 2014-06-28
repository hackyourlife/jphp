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
	abstract public function setReference($reference);
	abstract public function getReference();
	abstract public function getName();
	abstract public function isInstanceOf($T);
}

class JavaArray extends JavaObject {
	public $array;
	public $length;
	public $type;
	private $jvm;
	private $reference;

	public function __construct(&$jvm, $length, $type) {
		$this->jvm = &$jvm;
		$this->length = $length;
		$this->type = $type;
		$this->array = array();
		$initial = 0;
		if(is_string($type)) {
			$initial = NULL;
		}
		for($i = 0; $i < $length; $i++) {
			$this->array[$i] = $initial;
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

	public function bstring($off = 0, $len = NULL) {
		$string = '';
		if($len === NULL) {
			$len = $this->length;
		}
		for($i = 0; $i < $len; $i++) {
			$string .= chr($this->array[$i + $off] & 0xFF);
		}
		return $string;
	}

	public function getName() {
		return "Array[{$this->type}]";
	}

	public function setReference($reference) {
		$this->reference = $reference;
	}

	public function getReference() {
		return $this->reference;
	}

	public function isInstanceOf($T) { // FIXME
		if($this->type == $T->getName()) {
			return true;
		}
		return false;
	}

	public function finalize() {
		if(is_string($this->type)) {
			foreach($this->array as $entry) {
				if($entry !== NULL) {
					$this->jvm->references->free($entry);
				}
			}
		}
	}

	public function dump() {
		foreach($this->array as $i => $entry) {
			print("#$i: $entry\n");
		}
	}

	public function call($name, $signature, $args, $trace) {
		// cloneable
		if($name !== 'clone') {
			throw new Exception();
		}
		if($signature !== '()Ljava/lang/Object;') {
			throw new Exception($signature);
		}

		$array = new JavaArray($this->jvm, $this->length, $this->type);
		for($i = 0; $i < $this->length; $i++) {
			$array->set($i, $this->array[$i]);
		}
		$arrayref = $this->jvm->references->newref();
		$this->jvm->references->set($arrayref, $array);
		$array->setReference($arrayref);
		return $arrayref;
	}
}

class StackTraceElement {
	private $class;
	private $method;
	private $instruction;
	private $native;

	public function __construct($class, $method, $instruction, $native = false) {
		$this->class = $class;
		$this->method = $method;
		$this->instruction = $instruction;
		$this->native = $native;
	}

	public function getClass() {
		return $this->class;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getInstruction() {
		return $this->instruction;
	}

	public function isNative() {
		return $this->native;
	}
}

class StackTrace {
	private $trace;

	public function __construct() {
		$this->trace = array();
	}

	public function push($class, $method, $instruction, $native = false) {
		array_push($this->trace, new StackTraceElement($class, $method, $instruction, $native));
	}

	public function pop() {
		return array_pop($this->trace);
	}

	public function getTrace() {
		return array_reverse($this->trace);
	}

	public function show() {
		$trace = $this->getTrace();
		$i = 1;
		foreach($trace as $item) {
			$class = str_replace('/', '.', $item->getClass());
			$method = $item->getMethod();
			$instruction = $item->getInstruction();
			$at = $item->isNative() ? 'native' : "PC=$instruction";
			print("#$i $class.$method($at)\n");
			$i++;
		}
	}
}
