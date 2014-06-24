<?php

define('JAVA_CLASS_MAGIC',			(int)0xCAFEBABE);
define('JAVA_CONSTANT_CLASS',			(int) 7);
define('JAVA_CONSTANT_FIELDREF',		(int) 9);
define('JAVA_CONSTANT_METHODREF',		(int)10);
define('JAVA_CONSTANT_INTERFACEMETHODREF',	(int)11);
define('JAVA_CONSTANT_STRING',			(int) 8);
define('JAVA_CONSTANT_INTEGER',			(int) 3);
define('JAVA_CONSTANT_FLOAT',			(int) 4);
define('JAVA_CONSTANT_LONG',			(int) 5);
define('JAVA_CONSTANT_DOUBLE',			(int) 6);
define('JAVA_CONSTANT_NAMEANDTYPE',		(int)12);
define('JAVA_CONSTANT_UTF8',			(int) 1);
define('JAVA_ACC_PUBLIC',			(int)0x0001);
define('JAVA_ACC_PRIVATE',			(int)0x0002);
define('JAVA_ACC_PROTECTED',			(int)0x0004);
define('JAVA_ACC_STATIC',			(int)0x0008);
define('JAVA_ACC_FINAL',			(int)0x0010);
define('JAVA_ACC_SUPER',			(int)0x0020);
define('JAVA_ACC_SYNCHRONIZED',			(int)0x0020);
define('JAVA_ACC_VOLATILE',			(int)0x0040);
define('JAVA_ACC_TRANSIENT',			(int)0x0080);
define('JAVA_ACC_NATIVE',			(int)0x0100);
define('JAVA_ACC_INTERFACE',			(int)0x0200);
define('JAVA_ACC_ABSTRACT',			(int)0x0400);
define('JAVA_ACC_STRICT',			(int)0x0800);
define('JAVA_FIELDTYPE_BYTE',			'B');
define('JAVA_FIELDTYPE_CHAR',			'C');
define('JAVA_FIELDTYPE_DOUBLE',			'D');
define('JAVA_FIELDTYPE_FLOAT',			'F');
define('JAVA_FIELDTYPE_INTEGER',		'I');
define('JAVA_FIELDTYPE_LONG',			'J');
define('JAVA_FIELDTYPE_CLASS',			'L');
define('JAVA_FIELDTYPE_SHORT',			'S');
define('JAVA_FIELDTYPE_BOOLEAN',		'Z');
define('JAVA_FIELDTYPE_ARRAY',			'[');

class JavaClass {
	public	$magic;
	public	$minor_version;
	public	$major_version;
	public	$constant_pool_count;
	public	$constant_pool;
	public	$access_flags;
	public	$this_class;
	public	$super_class;
	public	$interfaces_count;
	public	$interfaces;
	public	$fields_count;
	public	$fields;
	public	$methods_count;
	public	$methods;
	public	$attributes_count;
	public	$attributes;

	public function __construct($stream) {
		$in = new DataInputStream($stream);

		$this->magic = $in->readInt();

		if($this->magic != JAVA_CLASS_MAGIC)
			throw new Exception(sprintf('Invalid class file: 0x%08X (expected 0x%08X)', $this->magic, JAVA_CLASS_MAGIC));

		// read class file
		$this->minor_version = $in->readShort();
		$this->major_version = $in->readShort();
		$this->constant_pool_count = $in->readShort();
		$this->constant_pool = self::readConstantPool($in, $this->constant_pool_count - 1);
		$this->acces_flags = $in->readShort();
		$this->this_class = $in->readShort();
		$this->super_class = $in->readShort();
		$this->interfaces_count = $in->readShort();
		$this->interfaces = array();
		for($i = 0; $i < $this->interfaces_count; $i++)
			$this->interfaces[] = $in->readShort();
		$this->fields_count = $in->readShort();
		$this->fields = self::readFields($in, $this->fields_count);
		$this->methods_count = $in->readShort();
		$this->methods = self::readMethods($in, $this->methods_count);
		$this->attributes_count = $in->readShort();
		$this->attributes = self::readAttributes($in, $this->attributes_count);

		// process attributes
		for($i = 0; $i < $this->fields_count; $i++)
			$this->fields[$i]['attributes'] = $this->processAttributes($this->fields[$i]['attributes']);
		for($i = 0; $i < $this->methods_count; $i++)
			$this->methods[$i]['attributes'] = $this->processAttributes($this->methods[$i]['attributes']);
		$this->attributes = $this->processAttributes($this->attributes);
	}

	public static function readConstantPool($in, $count) {
		$pool = array('<0>');
		for($i = 0; $i < $count; $i++) {
			$type = $in->readByte();
			$entry = array('type' => $type);
			$taketwo = false;
			switch($type) {
				case JAVA_CONSTANT_CLASS:
					$entry['name_index'] = $in->readShort();
					break;
				case JAVA_CONSTANT_FIELDREF:
				case JAVA_CONSTANT_METHODREF:
				case JAVA_CONSTANT_INTERFACEMETHODREF:
					$entry['class_index'] = $in->readShort();
					$entry['name_and_type_index'] = $in->readShort();
					break;
				case JAVA_CONSTANT_STRING:
					$entry['string_index'] = $in->readShort();
					break;
				case JAVA_CONSTANT_INTEGER:
				case JAVA_CONSTANT_FLOAT:
					$entry['bytes'] = $in->readInt();
					break;
				case JAVA_CONSTANT_LONG:
				case JAVA_CONSTANT_DOUBLE:
					$entry['high_bytes'] = $in->readInt();
					$entry['low_bytes'] = $in->readInt();
					$taketwo = true;
					break;
				case JAVA_CONSTANT_NAMEANDTYPE:
					$entry['name_index'] = $in->readShort();
					$entry['descriptor_index'] = $in->readShort();
					break;
				case JAVA_CONSTANT_UTF8:
					$entry['length'] = $in->readShort();
					$entry['bytes'] = $in->read($entry['length']);
					break;
				default:
					throw new Exception(sprintf('Unknown constant pool entry type: %d', $type));
			}
			$pool[] = $entry;
			if($taketwo) {
				$pool[] = 'unusable';
				$i++;
			}
		}
		return $pool;
	}

	public static function readFields($in, $count) {
		$fields = array();
		for($i = 0; $i < $count; $i++) {
			$entry = array();
			$entry['access_flags'] = $in->readShort();
			$entry['name_index'] = $in->readShort();
			$entry['descriptor_index'] = $in->readShort();
			$entry['attributes_count'] = $in->readShort();
			$entry['attributes'] = self::readAttributes($in, $entry['attributes_count']);
			$fields[] = $entry;
		}
		return $fields;
	}

	public static function readAttributes($in, $count) {
		$attributes = array();
		for($i = 0; $i < $count; $i++) {
			$attribute = array();
			$attribute['attribute_name_index'] = $in->readShort();
			$attribute['attribute_length'] = $in->readInt();
			$attribute['info'] = $in->read($attribute['attribute_length']);
			$attributes[] = $attribute;
		}
		return $attributes;
	}

	public static function readMethods($in, $count) {
		$methods = array();
		for($i = 0; $i < $count; $i++) {
			$method = array();
			$method['access_flags'] = $in->readShort();
			$method['name_index'] = $in->readShort();
			$method['descriptor_index'] = $in->readShort();
			$method['attributes_count'] = $in->readShort();
			$method['attributes'] = self::readAttributes($in, $method['attributes_count']);
			$methods[] = $method;
		}
		return $methods;
	}

	private function processAttributes($attributes) {
		for($i = 0; $i < count($attributes); $i++)
			$attributes[$i] = $this->processAttribute($attributes[$i]);
		return $attributes;
	}

	private function processAttribute($attribute) {
		$type_id = $attribute['attribute_name_index'];
		if(($type_id == 0) || ($type_id >= $this->constant_pool_count))
			throw new Exception('invalid constant pool index');
		$type = $this->constant_pool[$type_id];
		if($type['type'] != JAVA_CONSTANT_UTF8)
			throw new Exception('invalid entry type');
		switch($type['bytes']) {
			case 'ConstantValue':
				return $this->getConstantValueAttribute($attribute);
			case 'Code':
				return $this->getCodeAttribute($attribute);
			case 'LineNumberTable':
				return $this->getLineNumberTableAttribute($attribute);
			case 'Exceptions':
				return $this->getExceptionsAttribute($attribute);
			case 'InnerClasses':
				return $this->getInnerClassesAttribute($attribute);
			case 'Synthetic':
				return $this->getSyntheticAttribute($attribute);
			case 'SourceFile':
				return $this->getSourceFileAttribute($attribute);
			default:
				//throw new Exception(sprintf('unknown attribute type: \'%s\'', $type['bytes']));
				//echo(sprintf('ignoring unknown attribute type: \'%s\'', $type['bytes']));
				//return $attribute;
				return NULL;
		}
		throw new Exception('error!');
	}

	public function getConstantValueAttribute($attribute) {
		$attribute['constantvalue_index'] = get16bit_BE(str2bin($attribute['info']));
		unset($attribute['info']);
		return $attribute;
	}

	public function getCodeAttribute($attribute) {
		$in = new DataInputStream(new StringInputStream($attribute['info']));
		$code = array();
		$attribute['max_stack'] = $in->readShort();
		$attribute['max_locals'] = $in->readShort();
		$attribute['code_length'] = $in->readInt();
		$attribute['code'] = $in->read($attribute['code_length']);
		$attribute['exception_table_length'] = $in->readShort();
		$attribute['exception_table'] = array();
		for($i = 0; $i < $attribute['exception_table_length']; $i++)
			$attribute['exception_table'][] = array(
				'start_pc' => $in->readShort(),
				'end_pc' => $in->readShort(),
				'handler_pc' => $in->readShort(),
				'catch_type' => $in->readShort()
			);
		$attribute['attributes_count'] = $in->readShort();
		$attribute['attributes'] = $this->processAttributes(self::readAttributes($in, $attribute['attributes_count']));
		$in->close();
		unset($attribute['info']);
		return $attribute;
	}

	public function getExceptionsAttribute($attribute) {
		$in = new DataInputStream(new StringInputStream($attribute['info']));
		$attribute['number_of_exceptions'] = $in->readShort();
		$attribute['exception_index_table'] = array();
		for($i = 0; $i < $attribute['number_of_exceptions']; $i++)
			$attribute['exception_index_table'][] = $in->readShort();
		$in->close();
		unset($attribute['info']);
		return $attribute;
	}

	public function getInnerClassesAttribute($attribute) {
		$in = new DataInputStream(new StringInputStream($attribute['info']));
		$attribute['number_of_inner_classes'] = $in->readShort();
		$attribute['classes'] = array();
		for($i = 0; $i < $attribute['number_of_inner_classes']; $i++)
			$attribute['classes'][] = array(
				'inner_class_info_index' => $in->readShort(),
				'outer_class_info_index' => $in->readShort(),
				'inner_name_index' => $in->readShort(),
				'inner_class_access_flags' => $in->readShort()
			);
		$in->close();
		unset($attribute['info']);
		return $attribute;
	}

	public function getSyntheticAttribute($attribute) {
		unset($attribute['info']);
		return $attribute;
	}

	public function getSourceFileAttribute($attribute) {
		$in = new DataInputStream(new StringInputStream($attribute['info']));
		$attribute['sourcefile_index'] = $in->readShort();
		$in->close();
		unset($attribute['info']);
		return $attribute;
	}

	public function getLineNumberTableAttribute($attribute) {
		$in = new DataInputStream(new StringInputStream($attribute['info']));
		$attribute['line_number_table_length'] = $in->readShort();
		$attribute['line_number_table'] = array();
		for($i = 0; $i < $attribute['line_number_table_length']; $i++)
			$attribute['line_number_table'][] = array(
				'start_pc' => $in->readShort(),
				'line_number' => $in->readShort()
			);
		$in->close();
		unset($attribute['info']);
		return $attribute;
	}

	public static function bits2float($bits) {
		if($bits == 0x7f800000)
			return INF;
		if($bits == 0xff800000)
			return -INF;
		if(
		    (($bits >= 0x7f800001) && ($bits <= 0x7fffffff)) ||
		    (($bits >= 0xff800001) && ($bits <= 0xffffffff)))
			return NAN;
		$s = (($bits >> 31) == 0) ? 1 : -1;
		$e = (($bits >> 23) & 0xff);
		$m = ($e == 0) ?
			($bits & 0x7fffff) << 1 :
			($bits & 0x7fffff) | 0x800000;
		return $s * $m * pow(2, $e - 150);
	}

	public static function bits2double($bits) {
		if($bits == 0x7ff0000000000000)
			return INF;
		if($bits == 0x7ff0000000000000)
			return -INF;
		if(
		    (($bits >= 0x7ff0000000000001) && ($bits <= 0x7fffffffffffffff)) ||
		    (($bits >= 0xfff0000000000001) && ($bits <= 0xffffffffffffffff)))
			return NAN;
		$s = (($bits >> 63) == 0) ? 1 : -1;
		$e = (int)(($bits >> 52) & 0x7ff);
		$m = ($e == 0) ?
			($bits & 0xfffffffffffff) << 1 :
			($bits & 0xfffffffffffff) | 0x10000000000000;
		return $s * $m * pow(2, $e - 1075);
	}

	public function __toString() {
		return sprintf(
			'magic=0x%08X,version=%d.%d,constantpool=%d,fields=%d,interfaces=%d,methods=%d,attributes=%d',
			$this->magic,
			$this->major_version,
			$this->minor_version,
			$this->constant_pool_count,
			$this->fields_count,
			$this->interfaces_count,
			$this->methods_count,
			$this->attributes_count
		);
	}
}
