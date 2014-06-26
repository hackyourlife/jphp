<?php

function Java_java_lang_Class_registerNatives(&$jvm, &$class, $args) {
	return;
}

function Java_java_lang_Class_desiredAssertionStatus0(&$jvm, &$class, $args) {
	print("desiredAssertionStatus0\n");
	return 0;
}

function Java_java_lang_Class_getClassLoader0(&$jvm, &$class, $args) {
	print("getClassLoader0\n");
	return NULL;
}

function Java_java_lang_Class_getPrimitiveClass(&$jvm, &$class, $args) {
	if($args[0] === NULL) {
		throw new NullPointerException();
	}
	$name = $jvm->references->get($args[0]);
	$value = $jvm->references->get($name->getField('value'));
	var_dump($value->string());
	return NULL; // FIXME
	throw new Exception('not implemented');
}
