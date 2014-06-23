<?php

class JavaClassStatic {
	private $jvm;
	private $classfile;
	private $nativemethods;
	private $staticvars;

	public function __construct(&$jvm, $classfile) {
		$this->jvm = $jvm;
		$this->classfile = $classfile;
		$this->nativemethods = array();
		$this->staticvars = array();
	}

	public function instantiate() {
		$instance = new JavaClassInstance($this);
		return $instance;
	}
}
