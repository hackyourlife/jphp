<?php

function Java_sun_reflect_Reflection_getCallerClass(&$jvm, &$class, $args, $trace) {
	$T = $trace->getTrace();
	$caller = $T[1];
	$caller_class = $jvm->getClass($caller->getClass());
	return $caller_class;
}
