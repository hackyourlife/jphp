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

	public function __construct(&$jvm, $name, &$classfile) {
		$this->jvm = &$jvm;
		$this->classfile = &$classfile;
		$this->nativemethods = array();
		$this->fields = array();
		$this->methods = array();
		$this->interfaces = array();
		$this->name = $name;
		$this->access_flags = $classfile->access_flags;
		foreach($classfile->fields as $id => $field) {
			$name = $classfile->constant_pool[$field['name_index']]['bytes'];
			$descriptor = $classfile->constant_pool[$field['descriptor_index']]['bytes'];
			$this->fields[$name] = (object)array(
				'id' => $id,
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

	public function saveState() {
		$super = $this->super;
		if($super !== NULL) {
			$super = $this->super->getName();
		}
		return serialize(array(
			'methods' => $this->methods,
			'name' => $this->name,
			'interfaces' => $this->interfaces,
			'access_flags' => $this->access_flags,
			'super' => $super,
			'fields' => $this->fields
		));
	}

	public function loadState($s, &$jvm) {
		$this->jvm = &$jvm;
		$data = unserialize($s);
		$this->methods = $data['methods'];
		$this->name = $data['name'];
		$this->interfaces = $data['interfaces'];
		$this->access_flags = $data['access_flags'];
		$this->super = $data['super'];
		$this->fields = $data['fields'];
	}

	public function rebuildReferences() {
		if(($this->super !== NULL) && is_string($this->super)) {
			$this->super = &$this->jvm->getStatic($this->super);
		}
	}

	public function isInterface() {
		return ($this->access_flags & JAVA_ACC_INTERFACE) ? true : false;
	}

	public function isAbstract() {
		return ($this->access_flags & JAVA_ACC_ABSTRACT) ? true : false;
	}

	public function getModifiers() {
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

	public function getMethodById($id) {
		if(!isset($this->classfile->methods[$id])) {
			throw new Exception();
		}
		return $this->classfile->methods[$id];
	}

	public function getMethod($name, $signature, $classname = NULL) {
		if(($classname !== NULL) && ($classname != $this->name)) {
			$method_info = $this->jvm->getStatic($classname)->getMethod($name, $signature);
			$method = &$method_info->method;
			$implemented_in = $classname;
			if($method['access_flags'] & JAVA_ACC_ABSTRACT) {
				$implemented_in = $this->findMethodClass($name, $signature);
				$method_info = $this->getMethod($name, $signature, $implemented_in);
				$method = &$method_info->method;
			}
			return (object)array(
				'method' => $method,
				'class' => $implemented_in
			);
		} else {
			$classname = $this->findMethodClass($name, $signature);
			if($classname === NULL) {
				throw new MethodNotFoundException($name, $signature);
			}
			if($classname == $this->name) {
				$methodId = $this->getMethodId($name, $signature);
				$method = $this->classfile->methods[$methodId];
				return (object)array(
					'method' => $method,
					'class' => $this->getName()
				);
			} else {
				$class = $this->jvm->getStatic($classname);
				$methodId = $class->getMethodId($name, $signature);
				$method = $class->getMethodById($methodId);
				return (object)array(
					'method' => $method,
					'class' => $classname
				);
			}
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

	public function getFieldId($name) {
		if(!isset($this->fields[$name])) {
			throw new NoSuchFieldException($name);
		}
		return $this->fields[$name]->id;
	}

	public function getFieldName($id) {
		if(!isset($this->classfile->fields[$id])) {
			throw new NoSuchFieldException($id);
		}
		$field = $this->classfile->fields[$id];
		$name = $this->classfile->constant_pool[$field['name_index']]['bytes'];
		return $name;
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
		foreach($this->fields as $name => $value) {
			ob_start();
			var_dump($value->value);
			$v = trim(ob_get_clean());
			print("$name: $v\n");
		}
	}

	public function showMethods() {
		foreach($this->methods as $name => $method) {
			foreach($method as $signature => $id) {
				print("$name$signature\n");
			}
		}
	}

	public function getDeclaredFields($publiconly = true) {
		$fields = array();
		foreach($this->fields as $name => $field) {
			if($publiconly && !($field['access_flags'] & JAVA_ACC_PUBLIC)) {
				continue;
			}
			$fields[] = (object)array(
				'name' => $name,
				'modifiers' => $field->access_flags,
				'signature' => $field->descriptor
			);
		}
		return $fields;
	}

	public function getDeclaredConstructors($publiconly = false) {
		$constructors = array();
		foreach($this->classfile->methods as $id => $constructor) {
			$name = $this->classfile->constant_pool[$constructor['name_index']]['bytes'];
			if($name !== '<init>') {
				continue;
			}
			if($publiconly && !($constructor['access_flags'] & JAVA_ACC_PUBLIC)) {
				continue;
			}
			$signature = $this->classfile->constant_pool[$constructor['descriptor_index']]['bytes'];
			$constructors[] = (object)array(
				'name' => $name,
				'modifiers' => $constructor['access_flags'],
				'signature' => $signature
			);
		}
		return $constructors;
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
		$method_info = $this->getMethod($name, $signature);
		$method = &$method_info->method;
		$implemented_in =  $method_info->class;
		$native = $this->isNative($method);
		if($native) {
			return $this->jvm->callNative($this, $name, $signature, $args, $this->getName(), $trace);
		}
		$interpreter = $this->getInterpreter($implemented_in);
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
