<?php

function Java_java_io_FileInputStream_initIDs(&$jvm, &$class, $args, $trace) {
	return;
}

function Java_java_io_FileInputStream_open(&$jvm, &$class, $args, $trace) {
	$filenamechars = $jvm->references->get($args[0])->getField('value');
	$filename = $jvm->references->get($filenamechars)->string();
	$class->filename = $filename;
	$path = $jvm->getFSPath($filename);
	if(!file_exists($path)) {
		$this->trace->push('java/io/FileInputStream', 'open', true);

		$exception = $this->jvm->instantiate($name);
		$exceptionref = $this->jvm->references->newref();
		$this->jvm->references->set($exceptionref, 'java/io/FileNotFoundException');
		$exception->setReference($exceptionref);
		$string = JavaString::newString($this->jvm, $filename);
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $this->trace);
		throw new JavaException($exceptionref);
	}
	$class->filehandle = fopen($path, 'rb');
}

function Java_java_io_FileInputStream_read(&$jvm, &$class, $args, $trace) {
	if(!isset($class->filehandle)) {
		$this->trace->push('java/io/IOException', 'read', true);
		$exception = $this->jvm->instantiate($name);
		$exceptionref = $this->jvm->references->newref();
		$this->jvm->references->set($exceptionref, 'java/io/IOException');
		$exception->setReference($exceptionref);
		$string = JavaString::newString($this->jvm, 'no file handle attached');
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $this->trace);
		throw new JavaException($exceptionref);
	}
	if(feof($class->filehandle)) {
		return -1;
	}
	return ord(fread($class->filehandle, 1));
}

function Java_java_io_FileInputStream_readBytes(&$jvm, &$class, $args, $trace) {
	if(!isset($class->filehandle)) {
		$this->trace->push('java/io/IOException', 'readBytes', true);
		$exception = $this->jvm->instantiate($name);
		$exceptionref = $this->jvm->references->newref();
		$this->jvm->references->set($exceptionref, 'java/io/IOException');
		$exception->setReference($exceptionref);
		$string = JavaString::newString($this->jvm, 'no file handle attached');
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $this->trace);
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
		$this->trace->push('java/io/IOException', 'readBytes', true);
		$exception = $this->jvm->instantiate($name);
		$exceptionref = $this->jvm->references->newref();
		$this->jvm->references->set($exceptionref, 'java/io/IOException');
		$exception->setReference($exceptionref);
		$string = JavaString::newString($this->jvm, 'no file handle attached');
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($string), NULL, $this->trace);
		throw new JavaException($exceptionref);
	}
	fclose($class->filehandle);
}
