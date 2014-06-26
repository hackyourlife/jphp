<?php

class JavaClassStatic {
	private $classfile;
	private $nativemethods;
	private $methods;
	private $name;
	private $interfaces;
	private $access_flags;
	public $super;
	public $fields;
	public $jvm;

	public function __construct(&$jvm, $name, $classfile) {
		$this->jvm = &$jvm;
		$this->classfile = $classfile;
		$this->nativemethods = array();
		$this->fields = array();
		$this->methods = array();
		$this->interfaces = array();
		$this->name = $name;
		$this->access_flags = $classfile->access_flags;
		foreach($classfile->fields as $field) {
			$name = $classfile->constant_pool[$field['name_index']]['bytes'];
			$descriptor = $classfile->constant_pool[$field['descriptor_index']]['bytes'];
			$this->fields[$name] = (object)array(
				'name' => $name,
				'descriptor' => $descriptor,
				'access_flags' => $field['access_flags'],
				'value' => JVM::defaultValue($descriptor)
			);
		}
		foreach($classfile->methods as $id => $method) {
			$name = $classfile->constant_pool[$method['name_index']]['bytes'];
			$signature = $classfile->constant_pool[$method['descriptor_index']]['bytes'];
			if(!isset($this->methods[$name])) {
				$this->methods[$name] = array();
			}
			$this->methods[$name][$signature] = $id;
		}
		if($classfile->super_class !== 0) {
			$super_name = $classfile->constant_pool[$classfile->constant_pool[$classfile->super_class]['name_index']]['bytes'];
			$this->super = $jvm->getStatic($super_name);
		} else {
			$this->super = NULL;
		}
		foreach($classfile->interfaces as $interface) {
			$name = $classfile->constant_pool[$classfile->constant_pool[$interface]['name_index']]['bytes'];
			$this->interfaces[$name] = $interface;
		}
	}

	public function initialize() {
		$trace = new StackTrace();
		$trace->push('org/hackyourlife/jvm/JavaClassStatic', 'initialize', 0, true);
		try {
			$this->call('<clinit>', '()V', NULL, $trace);
		} catch(MethodnotFoundException $e) {
		}
	}

	public function isInterface() {
		return ($this->access_flags & JAVA_ACC_INTERFACE) ? true : false;
	}

	public function isAbstract() {
		return ($this->access_flags & JAVA_ACC_ABSTRACT) ? true : false;
	}

	public function getAccessFlags() {
		return $this->access_flags;
	}

	public function isInstanceOf($class) {
		if($this->getName() == $class->getName()) {
			return true;
		}
		if($class->isInterface()) {
			return $this->hasInterface($class);
		}
		if($this->super !== NULL) {
			return $this->super->isInstanceOf($class);
		}
		return false;
	}

	public function hasInterface($class) {
		if(isset($this->interfaces[$class->getName()])) {
			return true;
		}
		if($this->super !== NULL) {
			return $this->super->hasInterface($class);
		}
		return false;
	}

	public function getName() {
		return $this->name;
	}

	public function getMethodId($name, $signature) {
		if(!isset($this->methods[$name][$signature])) {
			throw new MethodNotFoundException($name, $signature);
		}
		return $this->methods[$name][$signature];
	}

	public function getMethod($name, $signature, $classname = NULL) {
		if(($classname !== NULL) && ($classname != $this->name)) {
			return $this->jvm->getStatic($classname)->getMethod($name, $signature);
		} else {
			$methodId = $this->getMethodId($name, $signature);
			return $this->classfile->methods[$methodId];
		}
	}

	public function findMethodClass($name, $signature) {
		if(isset($this->methods[$name][$signature])) {
			return $this->getName();
		}
		if($this->super !== NULL) {
			return $this->super->findMethodClass($name, $signature);
		}
		return NULL;
	}

	public function isNative($method) {
		return $method['access_flags'] & JAVA_ACC_NATIVE ? true : false;
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
				$this->jvm->references->free($this->fields[$name]->value);
			} catch(NoSuchReferenceException $e) {
			}
			if(!is_string($value) && ($value !== NULL)) {
				try {
					$this->jvm->references->useref($value);
				} catch(NoSuchReferenceException $e) {
					printException($e);
				}
			}
		}
		$this->fields[$name]->value = $value;
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

	public function getInterpreter($classname = NULL) {
		if(($classname !== NULL) && ($classname != $this->name)) {
			$classfile = $this->jvm->getStatic($classname)->getClass();
			return new Interpreter($this->jvm, $classfile);
		} else {
			return new Interpreter($this->jvm, $this->classfile);
		}
	}

	public function call($name, $signature, $args = NULL, $trace = NULL) {
		$method = $this->getMethod($name, $signature);
		$native = $this->isNative($method);
		if($native) {
			return $this->jvm->callNative($this, $name, $signature, $args, $this->getName(), $trace);
		}
		$interpreter = new Interpreter($this->jvm, $this->classfile);
		$interpreter->setMethod($method, $args);
		if($trace !== NULL) {
			$interpreter->setTrace($trace);
		}
		$pc = $interpreter->execute();
		$result = $interpreter->getResult();
		$interpreter->cleanup();
		return $result;
	}

	public function getClass() {
		return $this->classfile;
	}

	public function &instantiate() {
		$instance = new JavaClassInstance($this);
		return $instance;
	}
}
