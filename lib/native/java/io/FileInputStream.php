<?php

function Java_java_io_FileInputStream_initIDs(&$jvm, &$class, $args, $trace) {
	return;
}

function Java_java_io_FileInputStream_open(&$jvm, &$class, $args, $trace) {
	$filenamechars = $jvm->references->get($args[0])->getField('value');
	$filename = $jvm->references->get($filenamechars)->string();
	$class->filename = $filename;
	$path = $jvm->getFSPath($filename);
	if(!file_exists($path) || !is_file($path)) {
		$trace->push('java/io/FileInputStream', 'open', true);

		$exception = $jvm->instantiate('java/io/FileNotFoundException');
		$exceptionref = $jvm->references->newref();
		$jvm->references->set($exceptionref, $exception);
		$exception->setReference($exceptionref);
		$string = JavaString::newString($jvm, $filename);
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $trace);
		throw new JavaException($exceptionref);
	}
	$class->filehandle = fopen($path, 'rb');
}

function Java_java_io_FileInputStream_read(&$jvm, &$class, $args, $trace) {
	if(!isset($class->filehandle)) {
		$trace->push('java/io/IOException', 'read', true);
		$exception = $jvm->instantiate($name);
		$exceptionref = $jvm->references->newref();
		$jvm->references->set($exceptionref, 'java/io/IOException');
		$exception->setReference($exceptionref);
		$string = JavaString::newString($jvm, 'no file handle attached');
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $trace);
		throw new JavaException($exceptionref);
	}
	if(feof($class->filehandle)) {
		return -1;
	}
	return ord(fread($class->filehandle, 1));
}

function Java_java_io_FileInputStream_readBytes(&$jvm, &$class, $args, $trace) {
	if(!isset($class->filehandle)) {
		$trace->push('java/io/IOException', 'readBytes', true);
		$exception = $jvm->instantiate('java/io/IOException');
		$exceptionref = $jvm->references->newref();
		$jvm->references->set($exceptionref, $exception);
		$exception->setReference($exceptionref);
		$string = JavaString::newString($jvm, 'no file handle attached');
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $trace);
		throw new JavaException($exceptionref);
	}
	if(feof($class->filehandle)) {
		return -1;
	}
	$buffer = $jvm->references->get($args[0]);
	$offset = $args[1];
	$length = $args[2];
	$bytes = fread($class->filehandle, $length);
	for($i = 0; $i < strlen($bytes); $i++) {
		$buffer->set($i + $offset, ord($bytes[$i]));
	}
	return strlen($bytes);
}

function Java_java_io_FileInputStream_close0(&$jvm, &$class, $args, $trace) {
	if(!isset($class->filehandle)) {
		$trace->push('java/io/IOException', 'close0', true);
		$exception = $jvm->instantiate('java/io/IOException');
		$exceptionref = $jvm->references->newref();
		$jvm->references->set($exceptionref, $exception);
		$exception->setReference($exceptionref);
		$string = JavaString::newString($jvm, 'no file handle attached');
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $trace);
		throw new JavaException($exceptionref);
	}
	fclose($class->filehandle);
}

function Java_java_io_FileInputStream_available(&$jvm, &$class, $args, $trace) {
	if(!isset($class->filehandle)) {
		$trace->push('java/io/IOException', 'available', true);
		$exception = $jvm->instantiate('java/io/IOException');
		$exceptionref = $jvm->references->newref();
		$jvm->references->set($exceptionref, $exception);
		$exception->setReference($exceptionref);
		$string = JavaString::newString($jvm, 'no file handle attached');
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $trace);
		throw new JavaException($exceptionref);
	}
	$stat = fstat($class->filehandle);
	$size = $stat['size'];
	$pos = ftell($class->filehandle);
	if($pos > $size) {
		return 0;
	} else {
		return $size - $pos;
	}
}
