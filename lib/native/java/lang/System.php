<?php

function Java_java_lang_System_registerNatives(&$jvm, &$class, $args) {
	$printstream = $jvm->instantiate('java/io/PrintStream');
	$ref = $jvm->references->newref();
	$jvm->references->set($ref, $printstream);
	print("initializing\n");
	try {
		$printstream->callSpecial($ref, '<init>', '(Ljava/io/OutputStream;)V', array(NULL));
	//} catch(MethodNotFoundException $e) {
	} catch(Exception $e) {
		printException($e);
	}
	print("done\n");
	$class->setField('out', $ref);
}

function Java_java_lang_System_initProperties(&$jvm, &$class, $args) {
	$props = array(
		'java.vendor' => 'hackyourlife',
		'os.name' => 'PHP',
		'os.version' => phpversion(),
		'os.arch' => 'unknown',
		'file.separator' => DIRECTORY_SEPARATOR,
		'path.separator' => PATH_SEPARATOR,
		'line.separator' => PHP_EOL,
	);
	foreach($props as $key => $value) {
		$properties->call('put', '(Ljava/lang/Object;Ljava/lang/Object;)Ljava/lang/Object;', array($key, $value));
	}
}

function Java_java_lang_System_currentTimeMillis(&$jvm, $args) {
	return microtime() / 1000;
}
