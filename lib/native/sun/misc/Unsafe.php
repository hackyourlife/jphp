<?php

function Java_sun_misc_Unsafe_registerNatives(&$jvm, &$class, $args, $trace) {
	return;
}

function Java_sun_misc_Unsafe_arrayBaseOffset(&$jvm, &$class, $args, $trace) {
	$array = $jvm->references->get($args[0]);
	return 0; // FIXME
}

function Java_sun_misc_Unsafe_arrayIndexScale(&$jvm, &$class, $args, $trace) {
	$array = $jvm->references->get($args[0]);
	return 0; // FIXME
}

function Java_sun_misc_Unsafe_addressSize(&$jvm, &$class, $args, $trace) {
	return 4; // FIXME
}

function Java_sun_misc_Unsafe_compareAndSwapObject(&$jvm, &$class, $args, $trace) {
	$objectref = $args[0];
	$object = $jvm->references->get($objectref);
	$fieldid = $args[1];
	$from = $args[2];
	$to = $args[3];
	$fieldname = $object->getFieldName($fieldid);
	$value = $object->getField($fieldname);
	if($value === $from) {
		$object->setField($fieldname, $to);
		return 1;
	} else {
		return 0;
	}
}

function Java_sun_misc_Unsafe_compareAndSwapInt(&$jvm, &$class, $args, $trace) {
	$objectref = $args[0];
	$object = $jvm->references->get($objectref);
	$fieldid = $args[1];
	$from = $args[2];
	$to = $args[3];
	$fieldname = $object->getFieldName($fieldid);
	$value = $object->getField($fieldname);
	if($value === $from) {
		$object->setField($fieldname, $to);
		return 1;
	} else {
		return 0;
	}
}

function Java_sun_misc_Unsafe_objectFieldOffset(&$jvm, &$class, $args, $trace) {
	$fieldref = $args[0];
	$field = $jvm->references->get($fieldref);
	$clazzref = $field->getField('clazz');
	$clazz = $jvm->references->get($clazzref);
	$fieldnameref = $jvm->references->get($field->getField('name'));
	$fieldname = $jvm->references->get($fieldnameref->getField('value'))->string();
	$fieldid = $jvm->getStatic($clazz->info->name)->getFieldId($fieldname);
	return $fieldid;
}

function Java_sun_misc_Unsafe_getIntVolatile(&$jvm, &$class, $args, $trace) {
	$objectref = $args[0];
	$object = $jvm->references->get($objectref);
	$fieldid = $args[1];
	$fieldname = $object->getFieldName($fieldid);
	$value = $object->getField($fieldname);
	return $value;
}

// direct memory manipulation
$MEMORY = array();
$POINTER = 0;
function Java_sun_misc_Unsafe_allocateMemory(&$jvm, &$class, $args, $trace) {
	global $MEMORY;
	global $POINTER;
	$bytes = $args[0];
	$address = ++$POINTER;
	$MEMORY[$address] = (object)array(
		'size' => $bytes,
		'value' => array()
	);
	print("[MEMORY] allocated $bytes bytes: address = $address\n");
	return $address;
}

function Java_sun_misc_Unsafe_freeMemory(&$jvm, &$class, $args, $trace) {
	global $MEMORY;
	$address = $args[0];
	unset($MEMORY[$address]);
	print("[MEMORY] freed $address\n");
}

function Java_sun_misc_Unsafe_putLong(&$jvm, &$class, $args, $trace) {
	global $MEMORY;
	$address = $args[0];
	$x = $args[1];
	print("[MEMORY] putLong to $address\n");
	$mem = &$MEMORY[$address]->value;
	$mem[0] = ($x >> 56) & 0xFF;
	$mem[1] = ($x >> 48) & 0xFF;
	$mem[2] = ($x >> 40) & 0xFF;
	$mem[3] = ($x >> 32) & 0xFF;
	$mem[4] = ($x >> 24) & 0xFF;
	$mem[5] = ($x >> 16) & 0xFF;
	$mem[6] = ($x >>  8) & 0xFF;
	$mem[7] =  $x        & 0xFF;
}

function Java_sun_misc_Unsafe_getByte(&$jvm, &$class, $args, $trace) {
	global $MEMORY;
	$address = $args[0];
	print("[MEMORY] readByte from $address\n");
	return s8($MEMORY[$address]->value[0]);
}
