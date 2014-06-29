<?php

function Java_java_io_WinNTFileSystem_initIDs(&$jvm, &$class, $args, $trace) {
	return 0;
}

function Java_java_io_WinNTFileSystem_getBooleanAttributes(&$jvm, &$class, $args, $trace) {
	$pathref = $jvm->references->get($args[0])->getField('path');
	$pathchars = $jvm->references->get($pathref)->getField('value');
	$path = $jvm->references->get($pathchars)->string();
	$real_path = $jvm->getFSPath($path);
	$flags = 0;
	if(file_exists($real_path)) {
		$flags |= 0x01; // BA_EXISTS
	}
	if(is_file($real_path)) {
		$flags |= 0x02; // BA_REGULAR
	}
	if(is_dir($real_path)) {
		$flags |= 0x04; // BA_DIRECTORY
	}
	$basename = basename($real_path);
	if($basename[0] == '.') {
		$flags |= 0x08; // BA_HIDDEN
	}
	return $flags;
}

function Java_java_io_WinNTFileSystem_getLength(&$jvm, &$class, $args, $trace) {
	$pathref = $jvm->references->get($args[0])->getField('path');
	$pathchars = $jvm->references->get($pathref)->getField('value');
	$path = $jvm->references->get($pathchars)->string();
	$real_path = $jvm->getFSPath($path);
	return filesize($real_path);
}
