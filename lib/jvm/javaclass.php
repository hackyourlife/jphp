<?php

class JavaClassInstance extends JavaObject {
	public $staticclass;
	private $fields;
	private $super;

	public function __construct(&$staticclass) {
		$this->staticclass = &$staticclass;
		$this->fields = array();
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
			try {
				$this->super->callSpecial('<init>', '()V');
			} catch(MethodNotFoundException $e) {
			}
		} else {
			$this->super = NULL;
		}
	}

	public function setReference($reference) {
		$this->reference = $reference;
	}

	public function getReference() {
		return $this->reference;
	}

	public function getName() {
		return $this->staticclass->getName();
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
		print("$count local variables\n");
		foreach($this->fields as $name => $value) {
			ob_start();
			var_dump($value->value);
			$v = trim(ob_get_clean());
			print("$name: $v\n");
		}
	}

	public function call($name, $signature, $args = NULL, $classname = NULL) {
		$method = $this->staticclass->getMethod($name, $signature, $classname);
		$native = $this->staticclass->isNative($method);
		if($native) {
			return $this->staticclass->jvm->callNative($this, $name, $signature, $args);
		}
		$interpreter = $this->staticclass->getInterpreter($classname);
		$a = array($this->getReference());
		if($args !== NULL) {
			for($i = 0; $i < count($args); $i++) {
				$a[] = $args[$i];
			}
		}
		$interpreter->setMethod($method, $a, true);
		$pc = $interpreter->execute();
		$result = $interpreter->getResult();
		$interpreter->cleanup();
		return $result;
	}

	public function callSpecial($name, $signature, $args = NULL, $classname = NULL) {
		//$ref = $this->staticclass->jvm->references->getReference($this);
		//var_dump($ref);
		return $this->call($name, $signature, $args, $classname);
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
		$chars = new JavaArray(strlen($s), JAVA_T_CHAR);
		for($i = 0; $i < strlen($s); $i++) {
			$chars->set($i, ord($s[$i]));
		}
		$this->dataref = $jvm->references->newref();
		$jvm->references->set($this->dataref, $chars);
		$this->setReference($jvm->references->newref());
	}

	public function initialize() {
		$this->callSpecial('<init>', '([C)V', array($this->dataref));
	}

	public function finalize() {
		parent::finalize();
		$this->jvm->references->free($this->dataref);
	}
}
