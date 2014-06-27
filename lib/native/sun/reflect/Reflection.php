<?php

function Java_sun_reflect_Reflection_getCallerClass(&$jvm, &$class, $args, $trace) {
	$T = $trace->getTrace();
	$caller = $T[1];
	$caller_class = $jvm->getClass($caller->getClass());
	return $caller_class;
}

function Java_sun_reflect_Reflection_getClassAccessFlags(&$jvm, &$class, $args, $trace) {
	$clazz = $jvm->references->get($args[0]);
	$classname = $clazz->info->name;
	$clazz = $jvm->getStatic($classname);
	return $clazz->getModifiers();
}
