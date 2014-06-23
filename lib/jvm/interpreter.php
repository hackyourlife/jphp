<?php

class Interpreter {
	private	$classfile;
	private	$currentMethodID;
	private	$pc;
	private	$stack;
	private	$references;

	public function __construct($classfile) {
		$this->classfile = $classfile;
		$this->stack = new ArgumentStack();
		$this->references = new InterpreterReferences();
	}

	public function setMethod($method_id, $parameters) {
	}

	public function execute($steps) {
		$code_length = 0;
		$code = NULL;
		for($i = 0; $i < $steps; $i++)
			$this->pc += $this->runCode($code, $this->pc, $this->stack, $this->references);
	}

	public function runCode($code, $pc, &$stack, &$references) {
		$bytes = 1;
		switch($code[$pc]) {
			case 0x32: { // aaload; throws NullPointerException, ArrayIndexOutOfBoundsException
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				if($array == NULL)
					throw new Exception('NullPointerException');
				$stack->push($array[$index]);
				break;
			}
			case 0x53: { // aastore, throws NullPointerException, ArrayIndexOutOfBoundsException, ArrayStoreException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x01: { // aconst_null
				$stack->push(NULL);
				break;
			}
			case 0x19: { // aload
				$index = $code[$pc + 1];
				$stack->push($ref);
				$bytes++;
				break;
			}
		}
		return 1;
	}
}

class ArgumentStack {
	private $stack = array();
	public function push($variable) {
		array_push($this->stack, $variable);
	}
	public function pop() {
		return array_pop($this->stack);
	}
}

class InterpreterReferences {
	private $references = array();
	public function get($ref) {
		if(!isset($this->references[$ref]))
			throw new Exception('reference not found!');
		return $this->references[$ref];
	}
	public function set($ref, $value) {
		$this->references[$ref] = $value;
	}
}
