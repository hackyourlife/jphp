<?php

function Java_java_lang_Object_registerNatives(&$jvm, &$class, $args, $trace) {
	return;
}

function Java_java_lang_Object_hashCode(&$jvm, &$class, $args, $trace) {
	print("[HASH] {$class->getName()}\n");
	return $class->getReference(); // FIXME
}

function Java_java_lang_Object_getClass(&$jvm, &$class, $args, $trace) {
	$classname = $class->getName();
	return $jvm->getClass($classname);
}

// threading
function Java_java_lang_Object_notifyAll(&$jvm, &$class, $args, $trace) {
	return; // FIXME
}
