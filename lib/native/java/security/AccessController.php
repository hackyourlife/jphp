<?php

function Java_java_security_AccessController_doPrivileged(&$jvm, &$class, $args, $trace) {
	$object = $jvm->references->get($args[0]);
	return $object->call('run', '()Ljava/lang/Object;', NULL, NULL, $trace);
}

function Java_java_security_AccessController_getStackAccessControlContext(&$jvm, &$class, $args, $trace) {
	return NULL; // FIXME
}
