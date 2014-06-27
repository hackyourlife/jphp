<?php

class JavaClassInstance extends JavaObject {
	public $staticclass;
	private $fields;
	private $super;
	private $reference;

	public function __construct(&$staticclass) {
		$this->staticclass = &$staticclass;
		$this->fields = array();
		$this->reference = NULL;
		foreach($staticclass->fields as $name => $field) {
			if(!($field->access_flags & JAVA_ACC_STATIC)) {
				$this->fields[$name] = clone $field;
			}
		}
		if($staticclass->super !== NULL) {
			$this->super = $staticclass->super->instantiate();
			$reference = $this->staticclass->jvm->references->newref();
			$this->staticclass->jvm->references->set($reference, $this->super);
			$this->super->setReference($reference);
			//try {
			//	$this->super->callSpecial('<init>', '()V');
			//} catch(MethodNotFoundException $e) {
			//}
		} else {
			$this->super = NULL;
		}
	}

	public function setReference($reference) {
		$this->reference = $reference;
	}

	public function getReference() {
		if($this->reference === NULL) {
			throw new Exception();
		}
		return $this->reference;
	}

	public function getName() {
		return $this->staticclass->getName();
	}

	public function isInstanceOf($name) {
		return $this->staticclass->isInstanceOf($name);
	}

	public function getField($name) {
		if(!isset($this->fields[$name])) {
			if($this->super !== NULL) {
				return $this->super->getField($name);
			} else {
				throw new NoSuchFieldException($name);
			}
		}
		return $this->fields[$name]->value;
	}

	public function setField($name, $value) {
		if(!isset($this->fields[$name])) {
			if($this->super !== NULL) {
				$this->super->setField($name, $value);
				return;
			} else {
				throw new NoSuchFieldException($name);
			}
		}
		$type = $this->fields[$name]->descriptor[0];
		if(($type == JAVA_FIELDTYPE_ARRAY) || ($type == JAVA_FIELDTYPE_CLASS)) {
			try {
				$this->staticclass->jvm->references->free($this->fields[$name]->value);
			} catch(NoSuchReferenceException $e) {
			}
			if(!is_string($value) && ($value !== NULL)) {
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
		if($this->super !== NULL) {
			$this->staticclass->jvm->references->free($this->super->reference);
		}
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
		foreach($this->fields as $name => $value) {
			ob_start();
			var_dump($value->value);
			$v = trim(ob_get_clean());
			print("$name: $v\n");
		}
		if($this->super !== NULL) {
			$this->super->dump();
		}
	}

	public function showMethods() {
		$this->staticclass->showMethods();
	}

	public function findMethodClass($name, $signature) {
		return $this->staticclass->findMethodClass($name, $signature);
	}

	public function call($name, $signature, $args = NULL, $classname = NULL, $trace = NULL) {
		if($trace == NULL) {
			throw new Exception();
		}
		$method_info = $this->staticclass->getMethod($name, $signature, $classname);
		$method = &$method_info->method;
		$implemented_in = $method_info->class;
		$native = $this->staticclass->isNative($method);
		if($native) {
			return $this->staticclass->jvm->callNative($this, $name, $signature, $args, $implemented_in, $trace);
		}
		$interpreter = $this->staticclass->getInterpreter($implemented_in);
		$a = array($this->getReference());
		if($args !== NULL) {
			for($i = 0; $i < count($args); $i++) {
				$a[] = $args[$i];
			}
		}
		$interpreter->setMethod($method, $a, true);
		if($trace !== NULL) {
			$interpreter->setTrace($trace);
		}
		$pc = $interpreter->execute();
		$result = $interpreter->getResult();
		$interpreter->cleanup();
		return $result;
	}

	public function callSpecial($name, $signature, $args = NULL, $classname = NULL, $trace = NULL) {
		return $this->call($name, $signature, $args, $classname, $trace);
	}

	public function callInterface($name, $signature, $args = NULL, $classname = NULL, $trace = NULL) {
		return $this->call($name, $signature, $args, $classname, $trace);
	}
}

class JavaString extends JavaClassInstance {
	private $jvm;
	private $string;
	private $dataref;
	public function __construct(&$jvm, $s) {
		parent::__construct($jvm->getStatic('java/lang/String'));
		$this->jvm = $jvm;
		$this->string = $s;
		$chars = new JavaArray($jvm, strlen($s), JAVA_T_CHAR);
		for($i = 0; $i < strlen($s); $i++) {
			$chars->set($i, ord($s[$i]));
		}
		$this->dataref = $jvm->references->newref();
		$jvm->references->set($this->dataref, $chars);
		$this->setReference($jvm->references->newref());
	}

	public function initialize() {
		$trace = new StackTrace();
		$trace->push('org/hackyourlife/jvm/JavaString', 'initialize', 0, true);
		$this->callSpecial('<init>', '([C)V', array($this->dataref), NULL, $trace);
	}

	public function finalize() {
		parent::finalize();
		$this->jvm->references->free($this->dataref);
	}

	public function getString() {
		return $this->string;
	}
}
