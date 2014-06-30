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

	public function getFieldId($name) {
		return $this->staticclass->getFieldId($name);
	}

	public function getFieldName($id) {
		return $this->staticclass->getFieldName($id);
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

	public function call($name, $signature, $args = NULL, $trace = NULL, $classname = NULL) {
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
		return $this->call($name, $signature, $args, $trace, $classname);
	}

	public function callInterface($name, $signature, $args = NULL, $classname = NULL, $trace = NULL) {
		return $this->call($name, $signature, $args, $trace);
	}
}

class JavaString extends JavaClassInstance {
	private $jvm;
	private $string;
	private $dataref;
	public static $strings = array();

	public function __construct(&$jvm, $s) {
		parent::__construct($jvm->getStatic('java/lang/String'));
		$this->jvm = &$jvm;
		$this->string = $s;
		$chars = new JavaArray($jvm, JavaString::strlen($s), JAVA_T_CHAR);
		for($i = 0; $i < JavaString::strlen($s); $i++) {
			$chars->set($i, JavaString::ord(JavaString::charAt($s, $i)));
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

	public static function charAt($str, $pos) {
		return mb_substr($str, $pos, 1, "UTF-8");
	}

	public static function strlen($str) {
		return mb_strlen($str, 'UTF-8');
	}

	public static function ord($char) {
		$lead = ord($char[0]);

		if ($lead < 0x80) {
			return $lead;
		} else if ($lead < 0xE0) {
			return (($lead & 0x1F) << 6)
				| (ord($char[1]) & 0x3F);
		} else if ($lead < 0xF0) {
			return (($lead &  0xF) << 12)
				| ((ord($char[1]) & 0x3F) <<  6)
				|  (ord($char[2]) & 0x3F);
		} else {
			return (($lead &  0x7) << 18)
				| ((ord($char[1]) & 0x3F) << 12)
				| ((ord($char[2]) & 0x3F) <<  6)
				|  (ord($char[3]) & 0x3F);
		}
	}

	public static function chr($intval) {
		return mb_convert_encoding(pack('n', $intval), 'UTF-8', 'UTF-16BE');
	}


	public static function intern(&$jvm, $objectref) {
		$string = $jvm->references->get($objectref);
		$valueref = $string->getField('value');
		$value = $jvm->references->get($valueref)->string();
		if(isset(static::$strings[$value])) {
			return static::$strings[$value];
		} else {
			static::$strings[$value] = $objectref;
			$jvm->references->useref($objectref); // make permanent
			return $objectref;
		}
	}

	public static function newString(&$jvm, $value) {
		if(isset(static::$strings[$value])) {
			return static::$strings[$value];
		} else {
			$string = new JavaString($jvm, $value);
			$jvm->references->set($string->getReference(), $string);
			$string->initialize();
			static::$strings[$value] = $string->getReference();
			return $string->getReference();
		}
	}
}
