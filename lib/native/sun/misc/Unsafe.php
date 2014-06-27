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
