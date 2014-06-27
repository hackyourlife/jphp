<?php

class JavaStandardOutputStream extends JavaClassInstance {
	private $jvm;
	private $string;
	private $dataref;
	public function __construct(&$jvm) {
		parent::__construct($jvm->getStatic('java/io/OutputStream'));
		$this->jvm = $jvm;
	}

	public function finalize() {
		parent::finalize();
		$this->jvm->references->free($this->dataref);
	}

	public function getString() {
		return $this->string;
	}

	public function call($name, $signature, $args = NULL, $classname = NULL, $trace = NULL) {
		if(($name == 'write') && ($signature == '(B)V')) {
			var_dump($args);
			exit(0);
			return;
		}
		parent::call($name, $signature, $args, $classname, $trace);
	}
}
