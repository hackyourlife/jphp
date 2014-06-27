<?php

function Java_java_lang_Object_registerNatives(&$jvm, &$class, $args, $trace) {
	return;
}

function Java_java_lang_Object_hashCode(&$jvm, &$class, $args, $trace) {
	print("[HASH] {$class->getName()}\n");
	return $class->getReference(); // FIXME
}
