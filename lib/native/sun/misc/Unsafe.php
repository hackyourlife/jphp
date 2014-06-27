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
