<?php

class Interpreter {
	private $jvm;
	private	$classfile;
	private	$pc;
	private	$stack;
	private	$references;
	private $variables;
	private $result;
	private $exception;
	private $trace;

	private static $debug = 0;
	private static $debug_cast = false;
	private static $debug_invoke = false;

	public function __construct(&$jvm, &$classfile) {
		$this->jvm = &$jvm;
		$this->classfile = &$classfile;
		$this->stack = new ArgumentStack();
		$this->references = new InterpreterReferences($jvm->references);
		$this->variables = array();
		$this->pc = 0;
		$this->finished = false;
		$this->result = false;
		$this->exception = false;
		$this->trace = new StackTrace();
	}

	public static function parseDescriptor($descriptor) {
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

	public function setMethod($method, $parameters = NULL, $special = false) {
		$this->method = $method;
		$code_attribute_id = false;
		foreach($method['attributes'] as $id => $attribute) {
			if($this->classfile->constant_pool[$attribute['attribute_name_index']]['bytes'] == 'Code') {
				$code_attribute_id = $id;
				break;
			}
		}
		if($code_attribute_id === false) {
			$class_name = $this->classfile->constant_pool[$this->classfile->constant_pool[$this->classfile->this_class]['name_index']]['bytes'];
			$method_name = $this->classfile->constant_pool[$this->method['name_index']]['bytes'];
			$descriptor = $this->classfile->constant_pool[$method['descriptor_index']]['bytes'];
			print("$class_name.$method_name:$descriptor\n");
			throw new NoCodeSegmentException();
		}
		$code_attribute = $method['attributes'][$code_attribute_id];
		$this->code_length = $code_attribute['code_length'];
		$this->code = str2bin($code_attribute['code']);
		$descriptor = $this->parseDescriptor($this->classfile->constant_pool[$method['descriptor_index']]['bytes']);
		for($i = 0; $i < $code_attribute['max_locals']; $i++) {
			$this->variables[$i] = 0;
		}
		if($parameters !== NULL) {
			$i = 0;
			$n = 0;
			if($special) {
				$this->variables[0] = $parameters[0];
				$i++;
				$parameters = array_slice($parameters, 1);
			}
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
		$this->this_class = $this->classfile->constant_pool[$this->classfile->constant_pool[$this->classfile->this_class]['name_index']]['bytes'];
		$this->this_method = $this->classfile->constant_pool[$this->method['name_index']]['bytes'];
	}

	public function setTrace($trace) {
		$this->trace = $trace;
	}

	public function getTrace() {
		return $this->trace;
	}

	public function getResult() {
		return $this->result;
	}

	public function cleanup() {
		$this->references->cleanup();
	}

	public function execute($steps = 0) {
		global $MNEMONICS;
		$code_length = $this->code_length;
		$class = $this->this_class;
		$method = $this->this_method;
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

			if(static::$debug > 1) {
				print("--------- $class:$method --------\n");
				print("stack:\n");
				$this->stack->dump();
				print("variables:\n");
				foreach($this->variables as $i => $v) {
					printf("%02d => %s\n", $i, $v);
				}
			}
			if(static::$debug) {
				$mnemonic = isset($MNEMONICS[$this->code[$this->pc]]) ? $MNEMONICS[$this->code[$this->pc]] : 'unknown';
				printf("[%08X] %02X %s\n", $this->pc, $this->code[$this->pc], $mnemonic);
			}

			try {
				//$this->runCode($this->code, $this->classfile->constant_pool, $this->pc, $this->stack, $this->references, $this->variables, $this->finished, $this->result, $this->exception, $this->trace, $this->jvm, $class, $method);
				$this->runCode();
				$i++;
			} catch(JavaException $e) {
				$exception = $this->references->get($e->getMessage());
				$trace = new StackTrace();
				$messageref = $exception->call('getMessage', '()Ljava/lang/String;', NULL, $trace);
				if($messageref !== NULL) {
					$message = $this->references->get($messageref);
					$chars = $this->references->get($message->getField('value'));
					print("[ERROR] uncaught {$exception->getName()}: {$chars->string()}\n");
				} else {
					print("[ERROR] uncaught {$exception->getName()}\n");
				}
				$exception->trace->show();
				exit(0);
			} catch(Exception $e) {
				print("[ERROR] exception in $class:$method\n");
				$this->trace->show();
				printException($e);
				exit(0);
				//throw $e;
			}

			// DEBUG
			if(static::$debug > 1) {
				print("stack:\n");
				$this->stack->dump();
				print("variables:\n");
				foreach($this->variables as $i => $v) {
					printf("%02d => %s\n", $i, $v);
				}
			}

			if($this->finished) {
				if(static::$debug > 1) {
					print("=====> RETURN\n");
				}
				if($this->exception) {
					$exception = $this->references->get($this->result);
					$trace = new StackTrace();
					$messageref = $exception->call('getMessage', '()Ljava/lang/String;', NULL, $trace);
					if($messageref !== NULL) {
						$message = $this->references->get($messageref);
						$chars = $this->references->get($message->getField('value'));
						print("[ERROR] uncaught {$exception->getName()}: {$chars->string()}\n");
					} else {
						print("[ERROR] uncaught {$exception->getName()}\n");
					}
					$exception->trace->show();
					exit(0);
					//throw new Exception();
				}
				return $this->pc;
			}
		}
		return $this->pc;
	}

	public function throwException($name, $message = NULL) {
		$this->finished = true;
		$this->exception = true;
		//$this->trace->push($this->this_class, $this->this_method, $this->pc);
		//$msg = $name;
		//if($message !== NULL) {
		//	$msg = "$name: $message";
		//}
		//throw new Exception($msg);

		$this->trace->push($this->this_class, $this->this_method, $this->pc);

		$exception = $this->jvm->instantiate($name);
		$exceptionref = $this->jvm->references->newref();
		$this->jvm->references->set($exceptionref, $exception);
		$exception->setReference($exceptionref);
		if($message !== NULL) {
			$string = JavaString::newString($this->jvm, $message);
			$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $this->trace);
		} else {
			$exception->callSpecial('<init>', '()V', NULL, NULL, $this->trace);
		}
		throw new JavaException($exceptionref);

	}

	//public static function runCode($code, $constants, &$pc, &$stack, &$references, &$variables, &$finished, &$result, &$exception, &$trace, &$jvm, $this_class, $this_method) {
	public function runCode() {
		$code = $this->code;
		$constants = $this->classfile->constant_pool;
		$pc = &$this->pc;
		$stack = &$this->stack;
		$references = &$this->references;
		$variables = &$this->variables;
		$finished = &$this->finished;
		$result = &$this->result;
		$exception = &$this->exception;
		$trace = &$this->trace;
		$jvm = &$this->jvm;
		$this_class = &$this->this_class;
		$this_method = &$this->this_method;
		$bytes = 1;
		switch($code[$pc]) {
			case 0x32: { // aaload; throws NullPointerException, ArrayIndexOutOfBoundsException
				$index = $stack->pop();
				$arrayref = $stack->pop();
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x53: { // aastore, throws NullPointerException, ArrayIndexOutOfBoundsException, ArrayStoreException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$array = $references->get($arrayref);
				$array->set($index, $value);
				if($value !== NULL) {
					$references->persistent($value);
				}
				break;
			}
			case 0x01: { // aconst_null
				$stack->push(NULL);
				break;
			}
			case 0x19: { // aload
				$index = $code[$pc + 1];
				$objectref = $variables[$index];
				$stack->push($objectref);
				$bytes++;
				break;
			}
			case 0x2a: { // aload_0
				$objectref = $variables[0];
				$stack->push($objectref);
				break;
			}
			case 0x2b: { // aload_1
				$objectref = $variables[1];
				$stack->push($objectref);
				break;
			}
			case 0x2c: { // aload_2
				$objectref = $variables[2];
				$stack->push($objectref);
				break;
			}
			case 0x2d: { // aload_3
				$objectref = $variables[3];
				$stack->push($objectref);
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
				$references->set($arrayref, new JavaArray($jvm, $length, $type));
				$stack->push($arrayref);
				break;
			}
			case 0xb0: { // areturn, throws IllegalMonitorStateException
				$result = $stack->pop();
				if($result !== NULL) {
					$references->persistent($result);
				}
				$finished = true;
				break;
			}
			case 0xbe: { // arraylength, throws NullPointerException
				$arrayref = $stack->pop();
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$array = $references->get($arrayref);
				if($array instanceof JavaClassInstance) {
					throw new Exception("#$arrayref = {$array->getName()}");
				}
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
				if($objectref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$result = $objectref;
				$finished = true;
				$exception = true;
				break; // FIXME: EXCEPTION
			}
			case 0x33: { // baload, throws NullPointerException, ArrayIndexOutOfBoundsException
				$index = $stack->pop();
				$arrayref = $stack->pop();
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push(s8($value));
				break;
			}
			case 0x54: { // bastore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0x10: { // bipush
				$byte = $code[$pc + 1];
				$bytes++;
				$stack->push(s8($byte));
				break;
			}
			case 0x34: { // caload, throws NullPointerException, ArrayIndexOutOfBoundsException
				$index = $stack->pop();
				$arrayref = $stack->pop();
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x55: { // castore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$array = $references->get($arrayref);
				$array->set($index, $value);
				break;
			}
			case 0xc0: { // checkcast, throws ClassCastException
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$constant = $constants[$index];
				$bytes += 2;
				$objectref = $stack->pop();
				$stack->push($objectref);
				if($objectref !== NULL) {
					$object = $references->get($objectref);
					$T = $jvm->getStatic($constants[$constants[$index]['name_index']]['bytes']);
					$result = $object->isInstanceOf($T) ? 1 : 0;
					if(self::$debug_cast) {
						print("[CHECKCAST] {$object->getName()} can cast to {$T->getName()} = $result\n");
					}
					if(!$result) {
						$this->throwException('java/lang/ClassCastException', "{$object->getName()} cannot cast to {$T->getName()}");
					}
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
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
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
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$array = $references->get($arrayref);
				$value = $array->get($index);
				$stack->push($value);
				break;
			}
			case 0x51: { // fastore, throws NullPointerException, ArrayIndexOutOfBoundsException
				$value = $stack->pop();
				$index = $stack->pop();
				$arrayref = $stack->pop();
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
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
				if($objectref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$object = $references->get($objectref);
				$field = $constants[$index];
				$class_name = $constants[$constants[$field['class_index']]['name_index']]['bytes'];
				$field_name = $constants[$constants[$field['name_and_type_index']]['name_index']]['bytes'];
				try {
					$value = $object->getField($field_name);
				} catch(NoSuchFieldException $e) {
					$object->dump();
					$this->throwException('java/lang/NoSuchFieldError', "$class_name:$field_name");
				}
				$stack->push($value);
				break;
			}
			case 0xb2: { // getstatic, throws IncopatibleClassChangeError, Error
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$field = $constants[$index];
				$class_name = $constants[$constants[$field['class_index']]['name_index']]['bytes'];
				$field_name = $constants[$constants[$field['name_and_type_index']]['name_index']]['bytes'];
				$class = $jvm->getStatic($class_name);
				try {
					$value = $class->getField($field_name);
				} catch(NoSuchFieldException $e) {
					$class->dump();
					$this->throwException('java/lang/NoSuchFieldError', "$class_name:$field_name");
				}
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
			case 0xc8: { // goto_w
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
				$result = s8($value); // FIXME: sign
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
				$result = s16($value & 0xFFFF);
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
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
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
			case 0xa4: { // if_icmple
				$branchbyte1 = $code[$pc + 1];
				$branchbyte2 = $code[$pc + 2];
				$branch = s16(($branchbyte1 << 8) | $branchbyte2);
				$bytes += 2;
				$value2 = $stack->pop();
				$value1 = $stack->pop();
				if($value1 <= $value2) {
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
				if($value !== NULL) {
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
				if($value === NULL) {
					$pc += $branch;
					$bytes = 0;
				}
				break;
			}
			case 0x84: { // iinc
				$index = $code[$pc + 1];
				$const = $code[$pc + 2];
				$bytes += 2;
				$variables[$index] += s8($const);
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
				if($objectref === NULL) {
					$result = 0;
				} else {
					$object = $references->get($objectref);
					$T = $jvm->getStatic($constants[$constants[$index]['name_index']]['bytes']);
					$result = $object->isInstanceOf($T) ? 1 : 0;
					if(self::$debug_cast) {
						print("[INSTANCEOF] {$object->getName()} instanceof {$T->getName()} = $result\n");
					}
				}
				$stack->push($result);
				break;
			}
			case 0xba: { // invokedynamic, throws WrongMethodTypeException, BootstrapMethodError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 4;
				// FIXME: correct handling of invokedynamic
				throw new Exception('not implemented');
				break;
			}
			case 0xb9: { // invokeinterface, throws IncompatibleClassChangeError, NullPointerException, IllegalAccessError, AbstractMethodError, UnsatisfiedLinkError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$count = $code[$pc + 3];
				$bytes += 4;
				// FIXME: correct handling of invokeinterface
				$method = $constants[$index];
				$method_info = $constants[$method['name_and_type_index']];
				$class_name = $constants[$constants[$method['class_index']]['name_index']]['bytes'];
				$method_name = $constants[$method_info['name_index']]['bytes'];
				$method_descriptor = $constants[$method_info['descriptor_index']]['bytes'];
				if(self::$debug_invoke) {
					print("INTERFACE: '$class_name'.'$method_name' : '$method_descriptor'\n");
				}
				$descriptor = Interpreter::parseDescriptor($method_descriptor);
				$argc = count($descriptor->args);
				$args = array();
				for($i = 0; $i < $argc; $i++) {
					$args[] = $stack->pop();
				}
				$args = array_reverse($args);
				$objectref = $stack->pop();
				if($objectref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$object = $references->get($objectref);
				$trace->push($this_class, $this_method, $pc);
				$value = $object->callInterface($method_name, $method_descriptor, $args, $class_name, $trace);
				$trace->pop();
				if($descriptor->returns != 'V') {
					$stack->push($value);
				}
				break;
			}
			case 0xb7: { // invokespecial, throws IncompatibleClassChangeError, NullPointerException, IllegalAccessError, AbstractMethodError, UnsatisfiedLinkError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$method = $constants[$index];
				$method_info = $constants[$method['name_and_type_index']];
				$class_name = $constants[$constants[$method['class_index']]['name_index']]['bytes'];
				$method_name = $constants[$method_info['name_index']]['bytes'];
				$method_descriptor = $constants[$method_info['descriptor_index']]['bytes'];
				if(self::$debug_invoke) {
					print("SPECIAL: '$class_name'.'$method_name' : '$method_descriptor'\n");
				}
				$descriptor = Interpreter::parseDescriptor($method_descriptor);
				$argc = count($descriptor->args);
				$args = array();
				for($i = 0; $i < $argc; $i++) {
					$args[] = $stack->pop();
				}
				$args = array_reverse($args);
				$objectref = $stack->pop();
				if($objectref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$object = $references->get($objectref);
				$trace->push($this_class, $this_method, $pc);
				$value = $object->callSpecial($method_name, $method_descriptor, $args, $class_name, $trace);
				$trace->pop();
				if($descriptor->returns != 'V') {
					$stack->push($value);
				}
				break;
			}
			case 0xb8: { // invokestatic, throws IncompatibleClassChangeError, UnsatisfiedLinkError, Error
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$method = $constants[$index];
				$method_info = $constants[$method['name_and_type_index']];
				$class_name = $constants[$constants[$method['class_index']]['name_index']]['bytes'];
				$method_name = $constants[$method_info['name_index']]['bytes'];
				$method_descriptor = $constants[$method_info['descriptor_index']]['bytes'];
				if(self::$debug_invoke) {
					print("STATIC: '$class_name'.'$method_name' : '$method_descriptor'\n");
				}
				$descriptor = Interpreter::parseDescriptor($method_descriptor);
				$argc = count($descriptor->args);
				$args = array();
				for($i = 0; $i < $argc; $i++) {
					$args[] = $stack->pop();
				}
				$args = array_reverse($args);
				$trace->push($this_class, $this_method, $pc);
				$value = $jvm->call($class_name, $method_name, $method_descriptor, $args, $trace);
				$trace->pop();
				if($descriptor->returns != 'V') {
					$stack->push($value);
				}
				break;
			}
			case 0xb6: { // invokevirtual, throws IncompatibleClassChangeError, NullPointerException, WrongMethodTypeException, AbstractMethodError, UnsatisfiedLinkError
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$method = $constants[$index];
				$method_info = $constants[$method['name_and_type_index']];
				$class_name = $constants[$constants[$method['class_index']]['name_index']]['bytes'];
				$method_name = $constants[$method_info['name_index']]['bytes'];
				$method_descriptor = $constants[$method_info['descriptor_index']]['bytes'];
				if(self::$debug_invoke) {
					print("VIRTUAL: '$class_name'.'$method_name' : '$method_descriptor'\n");
				}
				$descriptor = Interpreter::parseDescriptor($method_descriptor);
				$argc = count($descriptor->args);
				$args = array();
				for($i = 0; $i < $argc; $i++) {
					$args[] = $stack->pop();
				}
				$args = array_reverse($args);
				$objectref = $stack->pop();
				if($objectref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$object = $references->get($objectref);
				$trace->push($this_class, $this_method, $pc);
				$value = $object->call($method_name, $method_descriptor, $args, $trace);
				$trace->pop();
				if($descriptor->returns != 'V') {
					$stack->push($value);
				}
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
				$constant = $constants[$index];
				switch($constant['type']) { // FIXME
				case JAVA_CONSTANT_CLASS:
					$class = $constants[$constant['name_index']]['bytes'];
					$value = $jvm->getClass($class);
					break;
				case JAVA_CONSTANT_FIELDREF:
					throw new Exception('not implemented: fieldref');
				case JAVA_CONSTANT_METHODREF:
					throw new Exception('not implemented: methodref');
				case JAVA_CONSTANT_INTERFACEMETHODREF:
					throw new Exception('not implemented: interface');
				case JAVA_CONSTANT_STRING:
					$string = $constants[$constant['string_index']]['bytes'];
					$value = JavaString::newString($jvm, $string);
					break;
				case JAVA_CONSTANT_INTEGER:
					$value = s32($constant['bytes']);
					break;
				case JAVA_CONSTANT_FLOAT:
					$value = JavaClass::bits2float($constant['bytes']);
					break;
				case JAVA_CONSTANT_LONG:
					throw new Exception('not implemented: long');
				case JAVA_CONSTANT_DOUBLE:
					throw new Exception('not implemented: double');
				case JAVA_CONSTANT_NAMEANDTYPE:
					throw new Exception('not implemented: nameandtype');
				case JAVA_CONSTANT_UTF8:
					throw new Exception('not implemented: utf8');
				default:
					throw new Exception("not implemented: unknown ({$constant['type']})");
				}
				$stack->push($value);
				break;
			}
			case 0x13: { // ldc_w
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$constant = $constants[$index];
				switch($constant['type']) { // FIXME
				case JAVA_CONSTANT_CLASS:
					$class = $constants[$constant['name_index']]['bytes'];
					$value = $jvm->getClass($class);
					break;
				case JAVA_CONSTANT_FIELDREF:
					throw new Exception('not implemented: fieldref');
				case JAVA_CONSTANT_METHODREF:
					throw new Exception('not implemented: methodref');
				case JAVA_CONSTANT_INTERFACEMETHODREF:
					throw new Exception('not implemented');
				case JAVA_CONSTANT_STRING:
					$string = $constants[$constant['string_index']]['bytes'];
					$value = JavaString::newString($jvm, $string);
					break;
				case JAVA_CONSTANT_INTEGER:
					$value = s32($constant['bytes']);
					break;
				case JAVA_CONSTANT_FLOAT:
					$value = JavaClass::bits2float($constant['bytes']);
					break;
				case JAVA_CONSTANT_LONG:
				case JAVA_CONSTANT_DOUBLE:
				case JAVA_CONSTANT_NAMEANDTYPE:
					throw new Exception('not implemented: nameandtype');
				case JAVA_CONSTANT_UTF8:
					throw new Exception('not implemented');
				default:
					throw new Exception("not implemented: unknown ({$constant['type']})");
				}
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
					$value = JavaClass::bits2double($value);
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
				$offset = 4 - $diff;
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
					$match1  = $code[$pc + $offset + ($i * 8) + 0x08];
					$match2  = $code[$pc + $offset + ($i * 8) + 0x09];
					$match3  = $code[$pc + $offset + ($i * 8) + 0x0A];
					$match4  = $code[$pc + $offset + ($i * 8) + 0x0B];
					$offset1 = $code[$pc + $offset + ($i * 8) + 0x0C];
					$offset2 = $code[$pc + $offset + ($i * 8) + 0x0D];
					$offset3 = $code[$pc + $offset + ($i * 8) + 0x0E];
					$offset4 = $code[$pc + $offset + ($i * 8) + 0x0F];
					$match = s32(($match1 << 24) | ($match2 << 16) | ($match3 << 8) | $match4);
					$jumpoffset = s32(($offset1 << 24) | ($offset2 << 16) | ($offset3 << 8) | $offset4);
					$pairs[$match] = $jumpoffset;
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
				$array = new JavaArray($jvm, $counts[0]); // FIXME: correct initialization of array
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
				$class = $constants[$type['name_index']]['bytes'];
				// FIXME: instantiate
				$objectref = $references->newref();
				$object = $jvm->instantiate($class);
				$object->setReference($objectref);
				$references->set($objectref, $object);
				$stack->push($objectref);
				break;
			}
			case 0xbc: { // newarray, throws NegativeArraySizeException
				$atype = $code[$pc + 1];
				$bytes++;
				$count = $stack->pop();
				$arrayref = $references->newref();
				$array = new JavaArray($jvm, $count, $atype);
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
				$value = $stack->pop();
				$objectref = $stack->pop();
				if($objectref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
				$object = $references->get($objectref);
				// FIXME: correct implementation of putfield
				$field = $constants[$index];
				$class_name = $constants[$constants[$field['class_index']]['name_index']]['bytes'];
				$field_name = $constants[$constants[$field['name_and_type_index']]['name_index']]['bytes'];
				$field_type = $constants[$constants[$field['name_and_type_index']]['descriptor_index']]['bytes'];
				$object->setField($field_name, $value);
				if((($field_type[0] == '[') || ($field_type[0] == 'L')) && ($value !== NULL)) {
					try {
						$references->get($value);
						$references->persistent($value);
					} catch(NoSuchReferenceException $e) {
					}
				}
				break;
			}
			case 0xb3: { // putstatic, throws IncompatibleClassChangeError, IllegalAccessError, Error
				$indexbyte1 = $code[$pc + 1];
				$indexbyte2 = $code[$pc + 2];
				$index = ($indexbyte1 << 8) | $indexbyte2;
				$bytes += 2;
				$type = $constants[$index];
				$value = $stack->pop();
				$field = $constants[$index];
				$class_name = $constants[$constants[$field['class_index']]['name_index']]['bytes'];
				$field_name = $constants[$constants[$field['name_and_type_index']]['name_index']]['bytes'];
				$field_type = $constants[$constants[$field['name_and_type_index']]['descriptor_index']]['bytes'];
				$class = $jvm->getStatic($class_name);
				$class->setField($field_name, $value);
				if((($field_type[0] == '[') || ($field_type[0] == 'L')) && ($value !== NULL)) {
					try {
						$references->get($value);
						$references->persistent($value);
					} catch(NoSuchReferenceException $e) {
					}
				}
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
				if($arrayref === NULL) {
					$this->throwException('java/lang/NullPointerException');
					return;
				}
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
				$bytes += 2;
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
				$offset = 4 - $diff;
				$defaultbyte1 = $code[$pc + $offset];
				$defaultbyte2 = $code[$pc + $offset + 1];
				$defaultbyte3 = $code[$pc + $offset + 2];
				$defaultbyte4 = $code[$pc + $offset + 3];
				$default = s32(($defaultbyte1 << 24) | ($defaultbyte2 << 16) | ($defaultbyte3 << 8) | $defaultbyte4);
				$lowbyte1  = $code[$pc + $offset + 0x04];
				$lowbyte2  = $code[$pc + $offset + 0x05];
				$lowbyte3  = $code[$pc + $offset + 0x06];
				$lowbyte4  = $code[$pc + $offset + 0x07];
				$highbyte1 = $code[$pc + $offset + 0x08];
				$highbyte2 = $code[$pc + $offset + 0x09];
				$highbyte3 = $code[$pc + $offset + 0x0A];
				$highbyte4 = $code[$pc + $offset + 0x0B];
				$low = s32(($lowbyte1 << 24) | ($lowbyte2 << 16) | ($lowbyte3 << 8) | $lowbyte4);
				$high = s32(($highbyte1 << 24) | ($highbyte2 << 16) | ($highbyte3 << 8) | $highbyte4);
				$count = $high - $low + 1;
				$offsets = array();
				for($i = 0; $i < $count; $i++) {
					$byte1 = $code[$pc + $offset + ($i * 4) + 0x0C];
					$byte2 = $code[$pc + $offset + ($i * 4) + 0x0D];
					$byte3 = $code[$pc + $offset + ($i * 4) + 0x0E];
					$byte4 = $code[$pc + $offset + ($i * 4) + 0x0F];
					$offsets[] = s32(($byte1 << 24) | ($byte2 << 16) | ($byte3 << 8) | $byte4);
				}
				$index = $stack->pop();
				if(($index < $low) || ($index > $high)) {
					$pc += $default;
					$bytes = 0;
				} else {
					$i = $index - $low;
					$pc += $offsets[$i];
					$bytes = 0;
				}
				break;
			}
			case 0xc4: { // wide
				throw new Exception('not implemented');
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
	private $references;
	private $nextref = 0;
	private $root;
	public function __construct($root) {
		$this->root = $root;
		$this->references = array();
	}
	public function dump() {
		print_r($this->references);
	}
	public function &get($ref) {
		if(!isset($this->references[$ref])) {
			$object = $this->root->useref($ref);
			$this->references[$ref] = true;
		}
		return $this->root->get($ref);
	}
	public function set($ref, &$value) {
		if(!is_integer($ref)) {
			throw new Exception('not a reference!');
		}
		$this->references[$ref] = true;
		$this->root->set($ref, $value);
	}
	public function persistent($ref) {
		$this->references[$ref] = false;
	}
	public function cleanup() {
		foreach($this->references as $reference => $transient) {
			if($transient) {
				$this->root->free($reference);
			} else if(JVM::getLogLevel() > 1) {
				$name = $this->root->get($reference)->getName();
				print("[GC] not cleaning up: $reference ($name)\n");
			}
		}
	}
	public function newref() {
		return $this->root->newref();
	}
}
