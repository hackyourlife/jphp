<?php

class JavaClassStatic {
	private $jvm;
	private $classfile;
	private $nativemethods;
	private $fields;
	private $methods;

	public function __construct(&$jvm, $classfile) {
		$this->jvm = $jvm;
		$this->classfile = $classfile;
		$this->nativemethods = array();
		$this->fields = array();
		$this->methods = array();
		foreach($classfile->methods as $id => $method) {
			$name = $classfile->constant_pool[$method['name_index']]['bytes'];
			$signature = $classfile->constant_pool[$method['descriptor_index']]['bytes'];
			if(!isset($this->methods[$name])) {
				$this->methods[$name] = array();
			}
			$this->methods[$name][$signature] = $id;
		}
	}

	public function getMethodId($name, $signature) {
		if(!isset($this->methods[$name][$signature])) {
			print_r($this->methods);
			throw new MethodNotFoundException($name, $signature);
		}
		return $this->methods[$name][$signature];
	}

	public function getMethod($name, $signature) {
		$methodId = $this->getMethodId($name, $signature);
		return $this->classfile->methods[$methodId];
	}

	public function call($name, $signature, $args = NULL) {
		$method = $this->getMethod($name, $signature);
		$interpreter = new Interpreter($this->jvm, $this->classfile);
		$interpreter->setMethod($method, $args);
		$pc = $interpreter->execute();
		return $interpreter->getResult();
	}

	public function instantiate() {
		$instance = new JavaClassInstance($this);
		return $instance;
	}
}
