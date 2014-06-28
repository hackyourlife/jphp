<?php

function Java_java_lang_ClassLoader_NativeLibrary_findBuiltinLib(&$jvm, &$class, $args, $trace) {
	$namechars = $jvm->references->get($args[0])->getField('value');
	$name = $jvm->references->get($namechars)->string();
	$path = $jvm->findBuiltinLib($name);
	return JavaString::newString($jvm, $path);
}

function Java_java_lang_ClassLoader_NativeLibrary_load(&$jvm, &$class, $args, $trace) {
	$pathchars = $jvm->references->get($args[0])->getField('value');
	$path = $jvm->references->get($pathchars)->string();
	$builtin = $args[1];
	$jvm->loadLibrary($path);
	$class->setField('loaded', 1);
}
