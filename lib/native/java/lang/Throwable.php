<?php

function Java_java_lang_Throwable_fillInStackTrace(&$jvm, &$class, $args, $trace) {
	$class->trace = clone $trace;
	return $class->getReference();
}
