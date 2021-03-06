<?php

function Java_java_lang_System_registerNatives(&$jvm, &$class, $args, $trace) {
	//$stdout = new JavaStandardOutputStream($jvm);
	//$stdoutref = $jvm->references->newref();
	//$jvm->references->set($stdoutref, $stdout);
	//$stdout->setReference($stdoutref);

	//$printstream = $jvm->instantiate('java/io/PrintStream');
	//$ref = $jvm->references->newref();
	//$jvm->references->set($ref, $printstream);
	//$printstream->setReference($ref);
	//$trace->push('java/lang/System', 'registerNatives', 0, true);
	//$printstream->callSpecial('<init>', '(Ljava/io/OutputStream;)V', array($stdoutref), NULL, $trace);
	//$trace->pop();

	//$class->setField('out', $ref);
	//$class->setField('err', $ref);
}

function Java_java_lang_System_initProperties(&$jvm, &$class, $args, $trace) {
	$props = array(
		'java.version' => '1.8.0',
		'java.vendor' => 'hackyourlife',
		//'java.vendor.url' => 'http://hackyourlife.lima-city.de/',
		//'java.home' => '/',
		'java.vm.version' => '1.0',
		'java.vm.vendor' => 'hackyourlife',
		'java.vm.name' => 'PHP Java VM',
		//'java.specification.version' => '1.8',
		//'java.specification.vendor' => 'Oracle Corporation',
		//'java.specification.name' => 'Java Platform API Specification',
		//'java.vm.specification.version' => '1.8',
		//'java.vm.specification.vendor' => 'Oracle Corporation',
		//'java.vm.specification.name' => 'Java Virtual Machine Specification',
		'java.class.version' => '52.0',
		'java.class.path' => $jvm->getClasspath(),
		'java.library.path' => $jvm->getLibraryPath(),
		//'java.io.tmpdir' => '/tmp',
		'os.name' => 'PHP',
		'os.version' => phpversion(),
		'os.arch' => 'unknown',
		'file.separator' => DIRECTORY_SEPARATOR,
		'path.separator' => PATH_SEPARATOR,
		'line.separator' => PHP_EOL,
		'file.encoding' => 'UTF-8'
	);
	$properties_ref = $args[0];
	$properties = $jvm->references->get($properties_ref);
	$trace->push('java/lang/System', 'initProperties', 0, true);
	foreach($props as $key => $value) {
		$string_key = JavaString::newString($jvm, $key);
		$string_value = JavaString::newString($jvm, $value);
		$properties->call('setProperty', '(Ljava/lang/String;Ljava/lang/String;)Ljava/lang/Object;', array($string_key, $string_value), $trace);
	}
	$trace->pop();
}

function Java_java_lang_System_currentTimeMillis(&$jvm, &$class, $args, $trace) {
	return microtime() / 1000;
}

function Java_java_lang_System_arraycopy(&$jvm, &$class, $args, $trace) {
	if(($args[0] === NULL) || ($args[2] === NULL)) {
		throw new NullPointerException();
	}
	$src = $jvm->references->get($args[0]);
	$srcPos = $args[1];
	$dst = $jvm->references->get($args[2]);
	$dstPos = $args[3];
	$length = $args[4];

	$tmp = array_slice($src->array, $srcPos, $length);
	array_splice($dst->array, $dstPos, $length, $tmp);
}

// I/O
function Java_java_lang_System_setIn0(&$jvm, &$class, $args, $trace) {
	$stdin = $args[0];
	$class->setField('in', $stdin);
}

function Java_java_lang_System_setOut0(&$jvm, &$class, $args, $trace) {
	$stdout = $args[0];
	$class->setField('out', $stdout);
}

function Java_java_lang_System_setErr0(&$jvm, &$class, $args, $trace) {
	$stderr = $args[0];
	$class->setField('err', $stderr);
}

// libraries
function Java_java_lang_System_mapLibraryName(&$jvm, &$class, $args, $trace) {
	$libnamechars = $jvm->references->get($args[0])->getField('value');
	$libname = $jvm->references->get($libnamechars)->string();
	$lib = $jvm->mapLibraryName($libname);
	return JavaString::newString($jvm, $lib);
}
