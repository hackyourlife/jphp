<?php

function Java_java_lang_Class_registerNatives(&$jvm, &$class, $args, $trace) {
	return;
}

function Java_java_lang_Class_desiredAssertionStatus0(&$jvm, &$class, $args, $trace) {
	return 0;
}

function Java_java_lang_Class_getClassLoader0(&$jvm, &$class, $args, $trace) {
	return NULL;
}

function Java_java_lang_Class_getPrimitiveClass(&$jvm, &$class, $args, $trace) {
	if($args[0] === NULL) {
		throw new NullPointerException();
	}
	$name = $jvm->references->get($args[0]);
	$value = $jvm->references->get($name->getField('value'));

	$primitives = array(
		'boolean'	=> JAVA_FIELDTYPE_BOOLEAN,
		'char'		=> JAVA_FIELDTYPE_CHAR,
		'float'		=> JAVA_FIELDTYPE_FLOAT,
		'double'	=> JAVA_FIELDTYPE_DOUBLE,
		'byte'		=> JAVA_FIELDTYPE_BYTE,
		'short'		=> JAVA_FIELDTYPE_SHORT,
		'int'		=> JAVA_FIELDTYPE_INTEGER,
		'long'		=> JAVA_FIELDTYPE_LONG
	);

	return $jvm->getPrimitiveClass($primitives[$value->string()]);
}

function Java_java_lang_Class_getDeclaredFields0(&$jvm, &$class, $args, $trace) {
	$publiconly = $args[0];
	$declared_fields = $jvm->getStatic($class->info->name)->getDeclaredFields($publiconly);
	$fields = new JavaArray($jvm, count($declared_fields), 'java/lang/reflect/Field');
	$fieldsref = $jvm->references->newref();
	$jvm->references->set($fieldsref, $fields);
	$fields->setReference($fieldsref);
	$trace->push('java/lang/Class', 'getDeclaredFields0', true);
	for($i = 0; $i < count($declared_fields); $i++) {
		$declared_field = $declared_fields[$i];
		$fieldname = JavaString::newString($jvm, $declared_field->name);

		$signature = JavaString::newString($jvm, $declared_field->signature);

		$type = NULL;
		if($declared_field->signature[0] == 'L') {
			$type_name = substr($declared_field->signature, 1, strlen($declared_field->signature) - 2);
			$type = $jvm->getClass($type_name);
		} else if($declared_field->signature[0] == '[') {
			$type = $jvm->getClass($declared_field->signature);
		} else {
			$type = $jvm->getPrimitiveClass($declared_field->signature);
		}
		$args = array(
			$jvm->getClass($class->info->name),
			$fieldname,
			$type,
			$declared_field->modifiers,
			0,
			$signature,
			NULL
		);

		$field = $jvm->instantiate('java/lang/reflect/Field');
		$fieldref = $jvm->references->newref();
		$jvm->references->set($fieldref, $field);
		$field->setReference($fieldref);
		$field->callSpecial('<init>', '(Ljava/lang/Class;Ljava/lang/String;Ljava/lang/Class;IILjava/lang/String;[B)V', $args, NULL, $trace);
		$fields->set($i, $fieldref);
	}
	$trace->pop();
	return $fieldsref;
}

function Java_java_lang_Class_getDeclaredConstructors0(&$jvm, &$class, $args, $trace) {
	$publiconly = $args[0];
	$declared_constructors = $jvm->getStatic($class->info->name)->getDeclaredConstructors($publiconly);
	$constructors = new JavaArray($jvm, count($declared_constructors), 'java/lang/reflect/Constructor');
	$constructorsref = $jvm->references->newref();
	$jvm->references->set($constructorsref, $constructors);
	$constructors->setReference($constructorsref);
	$trace->push('java/lang/Class', 'getDeclaredConstructors0', true);
	for($i = 0; $i < count($declared_constructors); $i++) {
		$declared_constructor = $declared_constructors[$i];
		$signature = $declared_constructor->signature;
		$parameters = Interpreter::parseDescriptor($signature);

		$signatureref = JavaString::newString($jvm, $signature);

		$types = new JavaArray($jvm, count($parameters->args), 'java/lang/Class');
		$typesref = $jvm->references->newref();
		$jvm->references->set($typesref, $types);
		$types->setReference($typesref);

		$exceptions = new JavaArray($jvm, 0, 'java/lang/Class');
		$exceptionsref = $jvm->references->newref();
		$jvm->references->set($exceptionsref, $exceptions);
		$exceptions->setReference($exceptionsref);

		$args = array(
			$jvm->getClass($class->info->name),
			$typesref,
			$exceptionsref,
			$declared_constructor->modifiers,
			0,
			$signatureref,
			NULL,
			NULL
		);

		$constructor = $jvm->instantiate('java/lang/reflect/Constructor');
		$constructorref = $jvm->references->newref();
		$jvm->references->set($constructorref, $constructor);
		$constructor->setReference($constructorref);
		$constructor->callSpecial('<init>', '(Ljava/lang/Class;[Ljava/lang/Class;[Ljava/lang/Class;IILjava/lang/String;[B[B)V', $args, NULL, $trace);
		$constructors->set($i, $constructorref);
	}
	$trace->pop();
	return $constructorsref;
}

function Java_java_lang_Class_forName0(&$jvm, &$class, $args, $trace) {
	$nameref = $args[0];
	$initialize = $args[1];
	$classloader = $args[2];

	$namechars = $jvm->references->get($nameref)->getField('value');
	$classname = $jvm->references->get($namechars)->string();

	$classname = str_replace('.', '/', $classname);

	try {
		if($initialize) {
			$jvm->load($classname);
			return $jvm->getClass($classname);
		} else {
			return $jvm->getClass($classname);
		}
	} catch(ClassNotFoundException $e) {
		$exception = $jvm->instantiate('java/lang/NoClassDefFoundError');
		$exceptionref = $jvm->references->newref();
		$jvm->references->set($exceptionref, $exception);
		$exception->setReference($exceptionref);
		$message = JavaString::newString($jvm, $classname);
		$exception->callSpecial('<init>', '(Ljava/lang/String;)V', array($message), NULL, $trace);
		throw new JavaException($exceptionref);
	}
}

function Java_java_lang_Class_getSuperclass(&$jvm, &$class, $args, $trace) {
	$classname = $class->info->name;
	$clazz = $jvm->getStatic($classname);
	$super = $clazz->super;
	if($super === NULL) {
		return $super;
	} else {
		$name = $super->getName();
		return $jvm->getClass($name);
	}
}

function Java_java_lang_Class_getModifiers(&$jvm, &$class, $args, $trace) {
	$classname = $class->info->name;
	$clazz = $jvm->getStatic($classname);
	return $clazz->getModifiers();
}

function Java_java_lang_Class_isInterface(&$jvm, &$class, $args, $trace) {
	$classname = $class->info->name;
	$clazz = $jvm->getStatic($classname);
	$interface = $clazz->isInterface();
	return $interface ? 1 : 0;
}

function Java_java_lang_Class_getName0(&$jvm, &$class, $args, $trace) {
	return $class->info->name;
}
