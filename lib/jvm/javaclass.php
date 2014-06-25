<?php

class JavaClassInstance extends JavaObject {
	public $staticclass;
	private $fields;

	public function __construct(&$staticclass) {
		$this->staticclass = $staticclass;
		$this->fields = array();
		foreach($staticclass->fields as $name => $field) {
			if(!($field->access_flags & JAVA_ACC_STATIC)) {
				$this->fields[$name] = clone $field;
			}
		}
	}

	public function getField($name) {
		if(!isset($this->fields[$name])) {
			throw new NoSuchFieldException($name);
		}
		return $this->fields[$name]->value;
	}

	public function setField($name, $value) {
		if(!isset($this->fields[$name])) {
			throw new NoSuchFieldException($name);
		}
		$type = $this->fields[$name]->descriptor[0];
		if(($type == JAVA_FIELDTYPE_ARRAY) || ($type == JAVA_FIELDTYPE_CLASS)) {
			try {
				$this->staticclass->jvm->references->free($this->fields[$name]->value);
			} catch(NoSuchReferenceException $e) {
			}
			if(!is_string($value)) {
				try {
					$this->staticclass->jvm->references->useref($value);
				} catch(NoSuchReferenceException $e) {
					printException($e);
				}
			}
		}
		$this->fields[$name]->value = $value;
	}

	public function finalize() {
		foreach($this->fields as $name => $field) {
			$type = $field->descriptor[0];
			if(($type == JAVA_FIELDTYPE_ARRAY) || ($type == JAVA_FIELDTYPE_CLASS)) {
				try {
					$this->staticclass->jvm->references->free($field->value);
				} catch(NoSuchReferenceException $e) {
				}
			}
		}
	}

	public function dump() {
		$count = count($this->fields);
		print("$count local variables\n");
		foreach($this->fields as $name => $value) {
			ob_start();
			var_dump($value->value);
			$v = trim(ob_get_clean());
			print("$name: $v\n");
		}
	}

	public function call($name, $signature, $args = NULL, $classname = NULL, $special = false) {
		$method = $this->staticclass->getMethod($name, $signature, $classname);
		$native = $this->staticclass->isNative($method);
		if($native) {
			return $this->staticclass->jvm->callNative($this, $name, $signature, $args);
		}
		$interpreter = $this->staticclass->getInterpreter($classname);
		$interpreter->setMethod($method, $args, $special);
		$pc = $interpreter->execute();
		$result = $interpreter->getResult();
		$interpreter->cleanup();
		return $result;
	}

	public function callSpecial($objectref, $name, $signature, $args = NULL, $classname = NULL) {
		//$ref = $this->staticclass->jvm->references->getReference($this);
		//var_dump($ref);
		$a = array($objectref);
		if($args !== NULL) {
			for($i = 0; $i < count($args); $i++) {
				$a[] = $args[$i];
			}
		}
		return $this->call($name, $signature, $a, $classname, true);
	}
}
