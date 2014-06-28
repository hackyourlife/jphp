<?php

function Java_java_io_FileOutputStream_initIDs(&$jvm, &$class, $args, $trace) {
	return;
}

function Java_java_io_FileOutputStream_writeBytes(&$jvm, &$class, $args, $trace) {
	$b = $args[0];
	$off = $args[1];
	$len = $args[2];
	$append = $args[3];
	$bytes = $jvm->references->get($b)->bstring($off, $len);
	$fd = $jvm->references->get($class->getField('fd'));
	$handle = $fd->getField('handle');
	if(($handle == 1) || ($handle == 2)) {
		print($bytes);
	}
}
