<?php

function s16($u16) {
	$sign = $u16 & 0x8000;
	if($sign) {
		return -(((~$u16) & 0xFFFF) + 1);
	} else {
		return $u16;
	}
}

function s32($u32) {
	$sign = $u32 & 0x80000000;
	if($sign) {
		return -(((~$u32) & 0xFFFFFFFF) + 1);
	} else {
		return $u32;
	}
}

function f64($bits) {
	$s = (($bits >> 63) == 0) ? 1 : -1;
	$e = (int)(($bits >> 52) & 0x7ff);
	$m = ($e == 0) ?
		($bits & 0xfffffffffffff) << 1 :
		($bits & 0xfffffffffffff) | 0x10000000000000;
	return $s * $m * pow(2, $e - 1075);
}

class Interpreter {
	private $jvm;
	private	$classfile;
	private	$pc;
	private	$stack;
	private	$references;
	private $variables;
	private $result;

	public function __construct(&$jvm, $classfile) {
		$this->jvm = $jvm;
		$this->classfile = $classfile;
		$this->stack = new ArgumentStack();
		$this->references = new InterpreterReferences();
		$this->variables = array();
		$this->pc = 0;
		$this->finished = false;
		$this->result = false;
	}

	public function parseDescriptor($descriptor) {
		$args = array();
		$returns = NULL;
		$i = 0;
		$state = 0;
		$object = '';
		$array = false;
		while($i < strlen($descriptor) && ($state != -1)) {
			$c = $descriptor[$i];
			switch($state) {
				case 0: {
					if($c === '(') {
						$state = 1;
					} else {
						throw new Exception('invalid method descriptor');
					}
					break;
				}
				case 1: {
					if($c == ')') {
						$returns = substr($descriptor, $i + 1);
						$state = -1;
					} else if($c == 'L') {
						$state = 2;
						$object = '';
					} else if($c == '[') {
						$array .= '[';
					} else {
						$args[] = "$array$c";
						$array = '';
					}
					break;
				}
				case 2: {
					if($c == ';') {
						$state = 1;
						$args[] = "{$array}L$object;";
						$array = '';
					} else {
						$object .= $c;
					}
					break;
				}
			}
			$i++;
		}
		return (object)array('args' => $args, 'returns' => $returns);
	}

	public function setMethod($method, $parameters = NULL) {
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
		$this->code = str2bin($code_attribute['code']);
		$descriptor = $this->parseDescriptor($this->classfile->constant_pool[$method['descriptor_index']]['bytes']);
		if($parameters !== NULL) {
			$i = 1;
			$n = 0;
			foreach($parameters as $value) {
				$this->variables[$i] = $value;
				$i++;
				$d = $descriptor->args[$n];
				$type = $d[strlen($d) - 1];
				if($type == JAVA_FIELDTYPE_DOUBLE || $type == JAVA_FIELDTYPE_LONG) {
					$this->variables[$i] = $value;
					$i++;
				}
				$n++;
			}
		}
	}

	public function getResult() {
		return $this->result;
	}

	public function execute($steps = 0) {
		$steps = 120;
		global $MNEMONICS;
		$code_length = $this->code_length;
		$i = 0;
		while(true) {
			if($steps != 0 && $i > $steps) {
				print("interrupting execution\n");
				return false;
			}
			if($this->pc >= $code_length) {
				print("we run out of code!\n");
				return false;
			}

			$mnemonic = isset($MNEMONICS[$this->code[$this->pc]]) ? $MNEMONICS[$this->code[$this->pc]] : 'unknown';
			printf("[%08X] %02X %s\n", $this->pc, $this->code[$this->pc], $mnemonic);
			$this->runCode($this->code, $this->classfile->constant_pool, $this->pc, $this->stack, $this->references, $this->variables, $this->finished, $this->result);
			$i++;

			// DEBUG
			//$this->stack->dump();
			//print_r($this->variables);
			//printf("---------------\n");

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
				$value = $array->get($index);
				$stack->push($value);
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
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value & 0xFF); // FIXME: SIGN
				break;
			}
			case 0x54: { // bastore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
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
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x55: { // castore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
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
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 + $value2;
				$stack->push($result);
				break;
			}
			case 0x31: { // daload, throws NullPointerException, ArrayIndexOutOfBoundsException
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x52: { // dastore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x98: // dcmpg
			case 0x97: { // dcmpl
				$value2 = $stack->pop();
				$value1 = $stack->pop();
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
				$value2 = $stack->pop();
				$value1 = $stack->pop();
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
				$value2 = $stack->pop();
				$value1 = $stack->pop();
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
				$value2 = $stack->pop();
				$value1 = $stack->pop();
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
			case 0x67: { //dsub
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 - $value2;
				$stack->push($result);
				break;
			}
			case 0x59: { // dup
				$value = $stack->pop();
				$stack->push($value);
				$stack->push($value);
				break;
			}
			case 0x5a: { // dup_x1
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$stack->push($value1);
				$stack->push($value2);
				$stack->push($value1);
				break;
			}
			case 0x5b: { // dup_x2
				// FIXME: double/long
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$value3 = $stack->pop();
				$stack->push($value1);
				$stack->push($value3);
				$stack->push($value2);
				$stack->push($value1);
				break;
			}
			case 0x5c: { // dup2
				// FIXME: double/long
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$stack->push($value2);
				$stack->push($value1);
				$stack->push($value2);
				$stack->push($value1);
				break;
			}
			case 0x5d: { // dup_x1
				// FIXME: double/long
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$value3 = $stack->pop();
				$stack->push($value2);
				$stack->push($value1);
				$stack->push($value3);
				$stack->push($value2);
				$stack->push($value1);
				break;
			}
			case 0x5e: { // dup2_x2
				// FIXME: double/long
				$value4 = $stack->pop();
				$value3 = $stack->pop();
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$stack->push($value2);
				$stack->push($value1);
				$stack->push($value4);
				$stack->push($value3);
				$stack->push($value2);
				$stack->push($value1);
				break;
			}
			case 0x8d: { // f2d
				$value = $stack->pop();
				$result = (double)$value;
				$stack->push($result);
				break;
			}
			case 0x8b: { // f2i
				$value = $stack->pop();
				$result = (int)$value;
				$stack->push($result);
				break;
			}
			case 0x8c: { // f2l
				$value = $stack->pop();
				$result = (int)$value; // FIXME: long
				$stack->push($result);
				break;
			}
			case 0x62: { // fadd
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 + $value2;
				$stack->push($result);
				break;
			}
			case 0x30: { // faload, throws NullPointerException, ArrayIndexOutOfBoundsException
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x51: { // fastore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x96: // fcmpg
			case 0x95: { // fcmpl
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = 0;
				if($value1 < $value2) {
					$result = -1;
				} else if($value1 > $value2) {
					$result = 1;
				}
				$stack->push($result);
				break;
			}
			case 0x0b: { // fconst_0
				$stack->push(0.0);
				break;
			}
			case 0x0c: { // fconst_1
				$stack->push(1.0);
				break;
			}
			case 0x0d: { // fconst_2
				$stack->push(2.0);
				break;
			}
			case 0x6e: { // fdiv
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 / $value2;
				$stack->push($result);
				break;
			}
			case 0x17: { // fload
				$index = $code[$pc + 1];
				$bytes++;
				$value = $variables[$index];
				$stack->push($value);
				break;
			}
			case 0x22: { // fload_0
				$value = $variables[0];
				$stack->push($value);
				break;
			}
			case 0x23: { // fload_1
				$value = $variables[1];
				$stack->push($value);
				break;
			}
			case 0x24: { // fload_2
				$value = $variables[2];
				$stack->push($value);
				break;
			}
			case 0x25: { // fload_3
				$value = $variables[3];
				$stack->push($value);
				break;
			}
			case 0x6a: { // fmul
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 * $value2;
				$stack->push($result);
				break;
			}
			case 0x76: { // fneg
				$value = $stack->pop();
				$result = -$value;
				$stack->push($result);
				break;
			}
			case 0x72: { // frem
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = fmod($value1, $value2);
				$stack->push($result);
				break;
			}
			case 0xae: { // freturn, throws IllegalMonitorStateException
				$value = $stack->pop();
				$result = $value;
				$finished = true;
				break;
			}
			case 0x38: { // fstore
				$index = $code[$pc + 1];
				$bytes++;
				$value = $stack->pop();
				$variables[$index] = $value;
				break;
			}
			case 0x43: { // fstore_0
				$value = $stack->pop();
				$variables[0] = $value;
				break;
			}
			case 0x44: { // fstore_1
				$value = $stack->pop();
				$variables[1] = $value;
				break;
			}
			case 0x45: { // fstore_2
				$value = $stack->pop();
				$variables[2] = $value;
				break;
			}
			case 0x46: { // fstore_3
				$value = $stack->pop();
				$variables[3] = $value;
				break;
			}
			case 0x66: { // fsub
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 - $value2;
				$stack->push($result);
				break;
			}
			case 0xb4: { // getfield, throws NullPointerException, IncopatibleClassChangeError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$objectref = $stack->pop();
				$object = $references->get($objectref);
				// FIXME
				$value = $object->getfield($index);
				$stack->push($value);
				break;
			}
			case 0xb2: { // getstatic, throws IncopatibleClassChangeError, Error
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				// FIXME: get class from vm
				//$value = $object->getfield($index);
				$value = NULL;
				$stack->push($value);
				break;
			}
			case 0xa7: { // goto
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes = 0;
				$pc += $branch;
				break;
			}
			case 0xc8: { // goto_
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branchbyte3 = $code[$pc + 2];
				$branchbyte4 = $code[$pc + 2];
				$branch = s32(($branchbyte1 << 24) | ($branchbyte2 << 16) | ($branchbyte3 << 8) | $branchbyte4);
				$bytes = 0;
				$pc += $branch;
				break;
			}
			case 0x91: { // i2b
				$value = $stack->pop();
				$result = $value & 0xFF; // FIXME: sign
				$stack->push($result);
				break;
			}
			case 0x92: { // i2c
				$value = $stack->pop();
				$result = $value & 0xFFFF;
				$stack->push($result);
				break;
			}
			case 0x87: { // i2d
				$value = $stack->pop();
				$result = (double)$value;
				$stack->push($result);
				break;
			}
			case 0x86: { // i2f
				$value = $stack->pop();
				$result = (float)$value;
				$stack->push($result);
				break;
			}
			case 0x85: { // i2l
				$value = $stack->pop();
				$result = $value; // FIXME: long
				$stack->push($result);
				break;
			}
			case 0x93: { // i2s
				$value = $stack->pop();
				$result = $value & 0xFFFF; // FIXME: sign
				$stack->push($result);
				break;
			}
			case 0x60: { // iadd
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 + $value2;
				$stack->push($result);
				break;
			}
			case 0x2e: { // iaload, throws NullPointerException, ArrayIndexOutOfBoundsException
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x7e: { // iand
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 & $value2;
				$stack->push($result);
				break;
			}
			case 0x4f: { // iastore
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x02: { // iconst_m1
				$value = -1;
				$stack->push($value);
				break;
			}
			case 0x03: { // iconst_0
				$value = 0;
				$stack->push($value);
				break;
			}
			case 0x04: { // iconst_1
				$value = 1;
				$stack->push($value);
				break;
			}
			case 0x05: { // iconst_2
				$value = 2;
				$stack->push($value);
				break;
			}
			case 0x06: { // iconst_3
				$value = 3;
				$stack->push($value);
				break;
			}
			case 0x07: { // iconst_4
				$value = 4;
				$stack->push($value);
				break;
			}
			case 0x08: { // iconst_5
				$value = 5;
				$stack->push($value);
				break;
			}
			case 0x6c: { // idiv, throws ArithmeticException
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 / $value2;
				$stack->push($result);
				break;
			}
			case 0xa5: { // if_acmpeq
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 === $value2) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0xa6: { // if_acmpne
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 !== $value2) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x9f: { // if_icmpeq
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 === $value2) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0xa0: { // if_icmpne
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 !== $value2) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0xa1: { // if_icmplt
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 < $value2) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0xa2: { // if_icmpge
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 >= $value2) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0xa3: { // if_icmpgt
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 > $value2) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0xa4: { // if_icmplt
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 < $value2) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x99: { // ifeq
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value = $stack->pop();
				if($value === 0) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x9a: { // ifne
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value = $stack->pop();
				if($value !== 0) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x9b: { // iflt
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value = $stack->pop();
				if($value < 0) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x9c: { // ifge
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value = $stack->pop();
				if($value >= 0) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x9d: { // ifgt
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value = $stack->pop();
				if($value > 0) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x9e: { // ifle
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value = $stack->pop();
				if($value <= 0) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0xc7: { // ifnonnull
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value = $stack->pop();
				if($value === NULL) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0xc6: { // ifnull
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value = $stack->pop();
				if($value !== NULL) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x84: { // iinc
				$index = $code[$pc + 1];
				$const = $code[$pc + 2];
				$bytes += 2;
				$variables[$index] += $const; // FIXME: sign
				break;
			}
			case 0x15: { // iload
				$index = $code[$pc + 1];
				$bytes++;
				$value = $variables[$index];
				$stack->push($value);
				break;
			}
			case 0x1a: { // iload_0
				$value = $variables[0];
				$stack->push($value);
				break;
			}
			case 0x1b: { // iload_1
				$value = $variables[1];
				$stack->push($value);
				break;
			}
			case 0x1c: { // iload_2
				$value = $variables[2];
				$stack->push($value);
				break;
			}
			case 0x1d: { // iload_3
				$value = $variables[3];
				$stack->push($value);
				break;
			}
			case 0x68: { // case imul
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 * $value2;
				$stack->push($result);
				break;
			}
			case 0x74: { // ineg
				$value = $stack->pop();
				$result = -$value;
				$stack->push($result);
				break;
			}
			case 0xc1: { // instanceof
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$objectref = $stack->pop();
				$result = 0;
				// FIXME: correct handling of instanceof
				$stack->push($result);
				break;
			}
			case 0xba: { // invokedynamic, throws WrongMethodTypeException, BootstrapMethodError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 4;
				// FIXME: correct handling of invokedynamic
				break;
			}
			case 0xb9: { // invokeinterface, throws IncompatibleClassChangeError, NullPointerException, IllegalAccessError, AbstractMethodError, UnsatisfiedLinkError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$count = $code[$pc + 3];
				$bytes += 4;
				// FIXME: correct handling of invokeinterface
				$objectref = $stack->pop();
				break;
			}
			case 0xb7: { // invokespecial, throws IncompatibleClassChangeError, NullPointerException, IllegalAccessError, AbstractMethodError, UnsatisfiedLinkError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				// FIXME: correct handling of invokespecial
				$objectref = $stack->pop();
				break;
			}
			case 0xb8: { // invokestatic, throws IncompatibleClassChangeError, UnsatisfiedLinkError, Error
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				// FIXME: correct handling of invokestatic
				break;
			}
			case 0xb6: { // invokevirtual, throws IncompatibleClassChangeError, NullPointerException, WrongMethodTypeException, AbstractMethodError, UnsatisfiedLinkError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				// FIXME: correct handling of invokevirtual
				$objectref = $stack->pop();
				break;
			}
			case 0x80: { // ior
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 | $value2;
				$stack->push($result);
				break;
			}
			case 0x70: { // irem
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 % $value2;
				$stack->push($result);
				break;
			}
			case 0xac: { // ireturn
				$value = $stack->pop();
				$result = $value;
				$finished = true;
				break;
			}
			case 0x78: { // ishl
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 << ($value2 & 0x1F);
				$stack->push($result);
				break;
			}
			case 0x7a: { // ishr
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 >> ($value2 & 0x1F);
				$stack->push($result);
				break;
			}
			case 0x36: { // istore
				$index = $code[$pc + 1];
				$bytes++;
				$value = $stack->pop();
				$variables[$index] = $value;
				break;
			}
			case 0x3b: { // istore_0
				$value = $stack->pop();
				$variables[0] = $value;
				break;
			}
			case 0x3c: { // istore_1
				$value = $stack->pop();
				$variables[1] = $value;
				break;
			}
			case 0x3d: { // istore_2
				$value = $stack->pop();
				$variables[2] = $value;
				break;
			}
			case 0x3e: { // istore_3
				$value = $stack->pop();
				$variables[3] = $value;
				break;
			}
			case 0x64: { // isub
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 - $value2;
				$stack->push($result);
				break;
			}
			case 0x7c: { // iushr
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$s = $value2 & 0x1F;
				$result = ($value1 >= 0) ? ($value1 >> $s) : (($value1 >> $s) + (2 << ~$s));
				$stack->push($result);
				break;
			}
			case 0x82: { // ixor
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 ^ $value2;
				$stack->push($result);
				break;
			}
			case 0xa8: { // jsr
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$address = $pc + $bytes;
				$stack->push($address);
				$bytes = 0;
				$pc += $branch;
				break;
			}
			case 0xc9: { // jsr_w
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branchbyte3 = $code[$pc + 3];
				$branchbyte4 = $code[$pc + 4];
				$branch = s32(($branchbyte1 << 24) | ($branchbyte2 << 16) | ($branchbyte3 << 8) | $branchbyte4);
				$bytes += 4;
				$address = $pc + $bytes;
				$stack->push($address);
				$bytes = 0;
				$pc += $branch;
				break;
			}
			case 0x8a: { // l2d
				$value = $stack->pop();
				$result = (double)$value;
				$stack->push($result);
				break;
			}
			case 0x89: { // l2f
				$value = $stack->pop();
				$result = (float)$value;
				$stack->push($result);
				break;
			}
			case 0x88: { // l2i
				$value = $stack->pop();
				$result = (int)$value;
				$stack->push($result);
				break;
			}
			case 0x61: { // ladd
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 + $value2;
				$stack->push($result);
				break;
			}
			case 0x2f: { // laload
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x7f: { // land
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 & $value2;
				$stack->push($result);
				break;
			}
			case 0x50: { // lastore
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x94: { // lcmp
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = 0;
				if($value1 > $value2) {
					$result = 1;
				} else if($value1 < $value2) {
					$result = -1;
				}
				break;
			}
			case 0x09: { // lconst_0
				$stack->push(0);
				break;
			}
			case 0x0a: { // lconst_1
				$stack->push(1);
				break;
			}
			case 0x12: { // ldc
				$index = $code[$pc + 1];
				$bytes++;
				$value = $constants[$index]; // FIXME
				$stack->push($value);
				break;
			}
			case 0x13: { // ldc_w
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$value = $constants[$index]; // FIXME
				$stack->push($value);
				break;
			}
			case 0x14: { // ldc2_w
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$constant = $constants[$index];
				$value = ($constant['high_bytes'] << 32) | $constant['low_bytes'];
				if($constant['type'] == JAVA_CONSTANT_DOUBLE) {
					$value = f64($value);
				}
				$stack->push($value);
				break;
			}
			case 0x6d: { // ldiv, throws ArithmeticException
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 / $value2;
				$stack->push($result);
				break;
			}
			case 0x16: { // lload
				$index = $code[$pc + 1];
				$bytes++;
				$value = $variables[$index];
				$stack->push($value);
				break;
			}
			case 0x1e: { // lload_0
				$value = $variables[0];
				$stack->push($value);
				break;
			}
			case 0x1f: { // lload_1
				$value = $variables[1];
				$stack->push($value);
				break;
			}
			case 0x20: { // lload_2
				$value = $variables[2];
				$stack->push($value);
				break;
			}
			case 0x21: { // lload_3
				$value = $variables[3];
				$stack->push($value);
				break;
			}
			case 0x69: { // lmul
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 * $value2;
				$stack->push($result);
				break;
			}
			case 0x75: { // lneg
				$value = $stack->pop();
				$result = -$value;
				$stack->push($value);
				break;
			}
			case 0xab: { // lookupswitch
				$boundary = ((int)($pc / 4)) * 4;
				$diff = $pc - $boundary;
				$offset = 3 - $diff; // FIXME: offset
				$defaultbyte1 = $code[$pc + $offset];
				$defaultbyte2 = $code[$pc + $offset + 1];
				$defaultbyte3 = $code[$pc + $offset + 2];
				$defaultbyte4 = $code[$pc + $offset + 3];
				$default = ($defaultbyte1 << 24) | ($defaultbyte2 << 16) | ($defaultbyte3 << 8) | $defaultbyte4;
				$npairs1 = $code[$pc + $offset + 4];
				$npairs2 = $code[$pc + $offset + 5];
				$npairs3 = $code[$pc + $offset + 6];
				$npairs4 = $code[$pc + $offset + 7];
				$npairs = ($npairs1 << 24) | ($npairs2 << 16) | ($npairs3 << 8) | $npairs4;
				$pairs = array();
				for($i = 0; $i < $npairs; $i++) {
					$match1 = $code[$pc + $offset + $i * 8 + 0x08];
					$match2 = $code[$pc + $offset + $i * 8 + 0x09];
					$match3 = $code[$pc + $offset + $i * 8 + 0x0A];
					$match4 = $code[$pc + $offset + $i * 8 + 0x0B];
					$offset1 = $code[$pc + $offset + $i * 8 + 0x0C];
					$offset2 = $code[$pc + $offset + $i * 8 + 0x0D];
					$offset3 = $code[$pc + $offset + $i * 8 + 0x0E];
					$offset4 = $code[$pc + $offset + $i * 8 + 0x0F];
					$match = s32(($match1 << 24) | ($match2 << 16) | ($match3 << 8) | $match4);
					$offset = s32(($offset1 << 24) | ($offset2 << 16) | ($offset30 << 8) | $offset4);
					$pairs[$match] = $offset;
				}
				$bytes += $offset + 8 + $npairs * 8;
				$key = $stack->pop();
				if(isset($pairs[$key])) {
					$pc += $pairs[$key];
					$bytes = 0;
				} else {
					$pc += $default;
					$bytes = 0;
				}
				break;
			}
			case 0x81: { // lor
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 | $value2;
				$stack->push($result);
				break;
			}
			case 0x71: { // lrem
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 % $value2;
				break;
			}
			case 0xad: { // lreturn, throws IllegalMonitorStateException
				$value = $stack->pop();
				$result = $value;
				$finished = true;
				break;
			}
			case 0x79: { // lshl
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$s = $value2 & 0x3f;
				$result = $value1 << $s;
				$stack->push($result);
				break;
			}
			case 0x7b: { // lshr
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$s = $value2 & 0x3f;
				$result = $value >> $s;
				$stack->push($result);
				break;
			}
			case 0x37: { // lstore
				$index = $code[$pc + 1];
				$bytes++;
				$value = $stack->pop();
				$variables[$index] = $value;
				break;
			}
			case 0x3f: { // lstore_0
				$value = $stack->pop();
				$variables[0] = $value;
				break;
			}
			case 0x40: { // lstore_1
				$value = $stack->pop();
				$variables[1] = $value;
				break;
			}
			case 0x41: { // lstore_2
				$value = $stack->pop();
				$variables[2] = $value;
				break;
			}
			case 0x42: { // lstore_3
				$value = $stack->pop();
				$variables[3] = $value;
				break;
			}
			case 0x65: { // lsub
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 - $value2;
				$stack->push($result);
				break;
			}
			case 0x7d: { // lushr
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$s = $value2 & 0x3f;
				$result = $value1 >> $s;
				if($value1 < 0) {
					$result += 2 << (~$s);
				}
				$stack->push($result);
				break;
			}
			case 0x83: { // lxor
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				$result = $value1 ^ $value2;
				$stack->push($result);
				break;
			}
			case 0xc2: { // monitorenter, throws NullPointerException
				$objectref = $stack->pop(); // FIXME
				break;
			}
			case 0xc3: { // monitorexit, throws NullPointerException
				$objectref = $stack->pop(); // FIXME
				break;
			}
			case 0xc5: { // multianewarray, throws IllegalAccessError, NegativeArraySizeException
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$dimensions = $code[$pc + 3];
				$bytes += 3;
				$counts = array();
				for($i = 0; $i < $dimensions; $i++) {
					$counts[] = $stack->pop();
				}
				$arrayref = $references->newref();
				$array = new JavaArray($counts[0]); // FIXME: correct initialization of array
				$references->set($arrayref, $array);
				$stack->push($arrayref);
				break;
			}
			case 0xbb: { // new, throws InstantiationError, Error
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$type = $constants[$index];
				print_r($type);
				// FIXME: instantiate
				$objectref = $references->newref();
				$object = NULL;
				$references->set($objectref, $object);
				$stack->push($objectref);
				break;
			}
			case 0xbc: { // newarray, throws NegativeArraySizeException
				$atype = $code[$pc + 1];
				$bytes++;
				$count = $stack->pop();
				$arrayref = $references->newref();
				$array = new JavaArray($count, $atype);
				$references->set($arrayref, $array);
				$stack->push($arrayref);
				break;
			}
			case 0x00: { // not
				break;
			}
			case 0x57: { // pop
				$value = $stack->pop();
				break;
			}
			case 0x58: { // pop2
				// FIXME: long, double
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				break;
			}
			case 0xb5: { // putfield, throws IncompatibleClassChangeError, IllegalAccessError, NullPointerException
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$type = $constants[$index];
				$value = $stack->pop();
				$objectref = $stack->pop();
				// FIXME: correct implementation of putfield
				break;
			}
			case 0xb3: { // putstatic, throws IncompatibleClassChangeError, IllegalAccessError, Error
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$type = $constants[$index];
				$value = $stack->pop();
				// FIXME: correct implementation of putstatic
				break;
			}
			case 0xa9: { // ret
				$index = $code[$pc + 1];
				$pc = $variables[$index];
				$bytes = 0;
				break;
			}
			case 0xb1: { // return, throws IllegalMonitorStateException
				$result = NULL;
				$finished = true;
				break;
			}
			case 0x35: { // saload, throws NullPointerException, throws ArrayIndexOutOfBoundsException
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$value = $arary->get($index);
				$stack->push($value);
				break;
			}
			case 0x56: { // sastore
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x11: { // sipush
				$byte1 = $code[$pc + 1];
				$byte2 = $code[$pc + 2];
				$value = s16(($byte1 << 8) | $byte2);
				$stack->push($value);
				break;
			}
			case 0x5f: { // swap
				$value1 = $stack->pop();
				$value2 = $stack->pop();
				$stack->push($value1);
				$stack->push($value2);
				break;
			}
			case 0xaa: { // tableswitch
				$boundary = ((int)($pc / 4)) * 4;
				$diff = $pc - $boundary;
				$offset = 3 - $diff; // FIXME: offset
				$defaultbyte1 = $code[$pc + $offset];
				$defaultbyte2 = $code[$pc + $offset + 1];
				$defaultbyte3 = $code[$pc + $offset + 2];
				$defaultbyte4 = $code[$pc + $offset + 3];
				$default = ($defaultbyte1 << 24) | ($defaultbyte2 << 16) | ($defaultbyte3 << 8) | $defaultbyte4;
				$lowbyte1 = $code[$pc + $offset + 4];
				$lowbyte2 = $code[$pc + $offset + 5];
				$lowbyte3 = $code[$pc + $offset + 6];
				$lowbyte4 = $code[$pc + $offset + 7];
				$highbyte1 = $code[$pc + $offset + 4];
				$highbyte2 = $code[$pc + $offset + 5];
				$highbyte3 = $code[$pc + $offset + 6];
				$highbyte4 = $code[$pc + $offset + 7];
				$index = $stack->pop();
				// FIXME: correct implementation of tableswitch
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
		return $this->nextref++;
	}
}
