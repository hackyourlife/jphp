<?php

class JavaClassStatic {
	private $jvm;
	private $classfile;
	private $nativemethods;
	private $fields;
	private $methods;
	private $name;

	public function __construct(&$jvm, $name, $classfile) {
		$this->jvm = $jvm;
		$this->classfile = $classfile;
		$this->nativemethods = array();
		$this->fields = array();
		$this->methods = array();
		$this->name = $name;
		foreach($classfile->fields as $field) {
			$name = $classfile->constant_pool[$field['name_index']]['bytes'];
			$descriptor = $classfile->constant_pool[$field['descriptor_index']]['bytes'];
			$this->fields[$name] = (object)array(
				'name' => $name,
				'descriptor' => $descriptor,
				'access_flags' => $field['access_flags'],
				'value' => $this->defaultValue($descriptor)
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
	}

	public function initialize() {
		try {
			$this->call('<clinit>', '()V');
		} catch(MethodnotFoundException $e) {
		}
	}

	public function getName() {
		return $this->name;
	}

	public function defaultValue($descriptor) {
		switch($descriptor[0]) {
		case JAVA_FIELDTYPE_BYTE:
		case JAVA_FIELDTYPE_CHAR:
		case JAVA_FIELDTYPE_INTEGER:
		case JAVA_FIELDTYPE_LONG:
		case JAVA_FIELDTYPE_SHORT:
		case JAVA_FIELDTYPE_BOOLEAN:
			return 0;
		case JAVA_FIELDTYPE_DOUBLE:
			return (double)0.0;
		case JAVA_FIELDTYPE_FLOAT:
			return (float)0.0;
		case JAVA_FIELDTYPE_CLASS:
		case JAVA_FIELDTYPE_ARRAY:
			return NULL;
		}
	}

	public function getMethodId($name, $signature) {
		if(!isset($this->methods[$name][$signature])) {
			throw new MethodNotFoundException($name, $signature);
		}
		return $this->methods[$name][$signature];
	}

	public function getMethod($name, $signature) {
		$methodId = $this->getMethodId($name, $signature);
		return $this->classfile->methods[$methodId];
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
		$this->fields[$name]->value = $value;
	}

	public function call($name, $signature, $args = NULL) {
		$method = $this->getMethod($name, $signature);
		$native = $this->isNative($method);
		if($native) {
			return $this->jvm->callNative($this, $name, $signature, $args);
		}
		$interpreter = new Interpreter($this->jvm, $this->classfile);
		$interpreter->setMethod($method, $args);
		$pc = $interpreter->execute();
		$result = $interpreter->getResult();
		$interpreter->cleanup();
		return $result;
	}

	public function instantiate() {
		$instance = new JavaClassInstance($this);
		return $instance;
	}
}
