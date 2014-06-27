<?php

function Java_java_lang_Thread_registerNatives(&$jvm, &$class, $args, $trace) {
	$class->setField('MIN_PRIORITY', 1);
	$class->setField('NORM_PRIORITY', 5);
	$class->setField('MAX_PRIORITY', 10);
	return;
}

function Java_java_lang_Thread_currentThread(&$jvm, &$class, $args, $trace) {
	$thread = $jvm->currentThread();
	return $thread;
}

function Java_java_lang_Thread_setPriority0(&$jvm, &$class, $args, $trace) {
	return; // FIXME
}

function Java_java_lang_Thread_isAlive(&$jvm, &$class, $args, $trace) {
	if(!isset($class->thread_info)) {
		return 0;
	}
	return $class->thread_info->alive;
}

function Java_java_lang_Thread_start0(&$jvm, &$class, $args, $trace) {
	if(!isset($class->thread_info)) {
		$class->thread_info = new stdClass();
	}
	$class->thread_info->alive = true;
}
