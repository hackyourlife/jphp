<?php

function Java_java_io_FileDescriptor_initIDs(&$jvm, &$class, $args, $trace) {
	return;
}

function Java_java_io_FileDescriptor_set(&$jvm, &$class, $args, $trace) {
	$class->fileDescriptor = $args[0];
	return $args[0];
}
