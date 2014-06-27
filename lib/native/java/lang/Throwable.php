<?php

function Java_java_lang_Throwable_fillInStackTrace(&$jvm, &$class, $args, $trace) {
	$class->trace = clone $trace;
	$class->trace->pop();
	$stack = $class->trace->getTrace();
	$count = count($class->trace->getTrace());
	// FIXME: remove correct number of frames (Exception initialization)
	$stop = NULL;
	for($i = 0; $i < $count; $i++) {
		$element = $stack[$i];
		$child = $class->isInstanceOf($jvm->getStatic($element->getClass()));
		if(!$child) {
			$stop = $i;
			break;
		}
	}
	if($stop !== NULL) {
		for($i = 0; $i < $stop; $i++) {
			$class->trace->pop();
		}
	}
	return $class->getReference();
}
