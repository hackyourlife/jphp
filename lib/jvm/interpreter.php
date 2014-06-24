<?php

class Interpreter {
	private	$classfile;
	private	$pc;
	private	$stack;
	private	$references;
	private $variables;
	private $result;

	public function __construct($classfile) {
		$this->classfile = $classfile;
		$this->stack = new ArgumentStack();
		$this->references = new InterpreterReferences();
		$this->variables = array();
		$this->pc = 0;
		$this->finished = false;
		$this->result = false;
	}

	public function setMethod($method, $parameters) {
		$this->method = $method;
		$code_attribute_id = false;
		foreach($method['attributes'] as $id => $attribute) {
			if($this->classfile->constant_pool[$attribute['attribute_name_index']]['bytes'] == 'Code') {
				$code_attribute_id = $id;
				break;
			}
		}
		if($code_attribute_id === false) {
			throw new NoCodeSegmentException();
		}
		$code_attribute = $method['attributes'][$code_attribute_id];
		$this->code_length = $code_attribute['code_length'];
		$this->code = array();
		for($i = 0; $i < $this->code_length; $i++) {
			$this->code[$i] = ord($code_attribute['code'][$i]);
		}
	}

	public function getResult() {
		return $this->result;
	}

	public function execute($steps = 0) {
		global $MNEMONICS;
		$code_length = $this->code_length;
		$i = 0;
		while(true) {
			if($steps != 0 && $i > $steps) {
				return false;
			}
			if($this->pc >= $code_length) {
				return false;
			}

			$mnemonic = isset($MNEMONICS[$this->code[$this->pc]]) ? $MNEMONICS[$this->code[$this->pc]] : 'unknown';
			printf("[%08X] %02X %s\n", $this->pc, $this->code[$this->pc], $mnemonic);
			$this->runCode($this->code, $this->classfile->constant_pool, $this->pc, $this->stack, $this->references, $this->variables, $this->finished, $this->result);
			$i++;

			if($this->finished) {
				return $this->pc;
			}
		}
		return $this->pc;
	}

	public static function runCode($code, $constants, &$pc, &$stack, &$references, &$variables, &$finished, &$result) {
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
			case 0x2a: { // aload_0
				$stack->push(0);
				break;
			}
			case 0x2b: { // aload_1
				$stack->push(1);
				break;
			}
			case 0x2c: { // aload_2
				$stack->push(2);
				break;
			}
			case 0x2d: { // aload_3
				$stack->push(3);
				break;
			}
			case 0xbd: { // anewarray, throws NegativeArraySizeException
				$arrayref = $references->newref();
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$bytes += 2;
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$length = $stack->pop();
				$type = $constants[$constants[$index]['name_index']]['bytes'];
				$references->set($arrayref, new JavaArray($length, $type));
				$stack->push($arrayref);
				break;
			}
			case 0xb0: { // areturn, throws IllegalMonitorStateException
				$result = $stack->pop();
				$finished = true;
				break;
			}
			case 0xbe: { // arraylength, throws NullPointerException
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$length = $array->length;
				$stack->push($length);
				break;
			}
			case 0x3a: { // astore
				$index = $code[$pc + 1];
				$bytes += 1;
				$objectref = $stack->pop();
				$variables[$index] = $objectref;
				break;
			}
			case 0x4b: { // astore_0
				$objectref = $stack->pop();
				$variables[0] = $objectref;
				break;
			}
			case 0x4c: { // astore_1
				$objectref = $stack->pop();
				$variables[1] = $objectref;
				break;
			}
			case 0x4d: { // astore_2
				$objectref = $stack->pop();
				$variables[2] = $objectref;
				break;
			}
			case 0x4e: { // astore_3
				$objectref = $stack->pop();
				$variables[3] = $objectref;
				break;
			}
			case 0xbf: { // athrow, throws NullPointerException, IllegalMonitorStateException
				$objectref = $stack->pop();
				$finished = true;
				break; // FIXME: EXCEPTION
			}
			case 0x33: { // baload, throws NullPointerException, ArrayIndexOutOfBoundsException
				$arrayref = $stack->pop();
				$index = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value & 0xFF); // FIXME: SIGN
				break;
			}
			case 0x54: { // bastore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$arrayref = $stack->pop();
				$index = $stack->pop();
				$value = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x10: { // bipush
				$byte = $code[$pc + 1];
				$bytes++;
				$stack->push($byte); // FIXME: SIGN
				break;
			}
			case 0x34: { // caload, throws NullPointerException, ArrayIndexOutOfBoundsException
				$arrayref = $stack->pop();
				$index = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x55: { // castore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$arrayref = $stack->pop();
				$index = $stack->pop();
				$value = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0xc0: { // checkcast, throws ClassCastException
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$constant = $constants[$index];
				print_r($constant);
				$bytes += 2;
				$objectref = $stack->pop();
				if($objectref == NULL) {
					$stack->push($objectref);
				} else {
					// FIXME
				}
				break;
			}
			case 0x90: { // d2f
				$value = $stack->pop();
				$result = (double)$value;
				$stack->push($result);
				break;
			}
			case 0x8e: { // d2i
				$value = $stack->pop();
				$result = (int)$value;
				$stack->push($result);
				break;
			}
			case 0x8f: { // d2l
				$value = $stack->pop();
				$result = (int)$value; // FIXME
				$stack->push($result);
				break;
			}
			case 0x63: { // dadd
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$result = $value1 + $value2;
				$stack->push($result);
				break;
			}
			case 0x31: { // daload, throws NullPointerException, ArrayIndexOutOfBoundsException
				$arrayref = $stack->pop();
				$index = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x52: { // dastore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$arrayref = $stack->pop();
				$index = $stack->pop();
				$value = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x98: // dcmpg
			case 0x97: { // dcmpl
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$result = 0;
				if($value1 < $value2) {
					$result = -1;
				} else if($value1 > $value2) {
					$result = 1;
				}
				$stack->push($result);
				break;
			}
			case 0x0e: { // dconst_0
				$stack->push(0.0);
				break;
			}
			case 0x0f: { // dconst_1
				$stack->push(1.0);
				break;
			}
			case 0x6f: { // ddiv
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$result = $value1 / $value2;
				$stack->push($value);
				break;
			}
			case 0x18: { // dload
				$index = $code[$pc + 1];
				$bytes++;
				$value = $variables[$index];
				$stack->push($value);
				break;
			}
			case 0x26: { // dload_0
				$value = $variables[0];
				$stack->push($value);
				break;
			}
			case 0x27: { // dload_1
				$value = $variables[1];
				$stack->push($value);
				break;
			}
			case 0x28: { // dload_2
				$value = $variables[2];
				$stack->push($value);
				break;
			}
			case 0x29: { // dload_3
				$value = $variables[3];
				$stack->push($value);
				break;
			}
			case 0x6b: { // dmul
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$result = $value1 * $value2;
				$stack->push($result);
				break;
			}
			case 0x77: { // dneg
				$value = $stack->pop();
				$result = -$value;
				$stack->push($value);
				break;
			}
			case 0x73: { // drem
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$result = fmod($value1, $value2);
				$stack->push($result);
				break;
			}
			case 0xaf: { // dreturn, throws IllegalMonitorStateException
				$value = $stack->pop();
				$result = $value;
				$finished = true;
				break;
			}
			case 0x39: { // dstore
				$index = $code[$pc + 1];
				$bytes++;
				$value = $stack->pop();
				$variables[$index] = $value;
				$variables[$index + 1] = $value;
				break;
			}
			case 0x47: { // dstore_0
				$value = $stack->pop();
				$variables[0] = $value;
				$variables[0 + 1] = $value;
				break;
			}
			case 0x48: { // dstore_1
				$value = $stack->pop();
				$variables[1] = $value;
				$variables[1 + 1] = $value;
				break;
			}
			case 0x49: { // dstore_2
				$value = $stack->pop();
				$variables[2] = $value;
				$variables[2 + 1] = $value;
				break;
			}
			case 0x4a: { // dstore_3
				$value = $stack->pop();
				$variables[3] = $value;
				$variables[3 + 1] = $value;
				break;
			}
			default: {
				throw new Exception("unknown opcode: " . $code[$pc]);
			}
		}
		$pc += $bytes;
		return $bytes;
	}
}

class ArgumentStack {
	private $stack = array();
	public function dump() {
		foreach($this->stack as $i => $v) {
			printf("%02d => %s\n", $i, $v);
		}
	}
	public function push($variable) {
		array_push($this->stack, $variable);
	}
	public function pop() {
		return array_pop($this->stack);
	}
}

class InterpreterReferences {
	private $references = array();
	private $nextref = 0;
	public function dump() {
		print_r($this->references);
	}
	public function get($ref) {
		if(!isset($this->references[$ref]))
			throw new Exception('reference not found!');
		return $this->references[$ref];
	}
	public function set($ref, $value) {
		$this->references[$ref] = $value;
	}
	public function newref() {
		$ref = $this->nextref++;
	}
}
