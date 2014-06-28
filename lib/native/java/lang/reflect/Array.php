<?php

function Java_java_lang_reflect_Array_newArray(&$jvm, &$class, $args, $type) {
	$componentType = $jvm->references->get($args[0]);
	$length = $args[1];
	$type = $componentType->info->name;
	if(isset($componentType->info->primitive)) {
		$type = JavaArray::primitive($componentType->info->name);
	}
	$array = new JavaArray($jvm, $length, $type);
	$arrayref = $jvm->references->newref();
	$jvm->references->set($arrayref, $array);
	$array->setReference($arrayref);
	return $arrayref;
}
