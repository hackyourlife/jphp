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
	$fields = new JavaArray($jvm, count($declared_fields), $class->info->name);
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
		$field->call('<init>', '(Ljava/lang/Class;Ljava/lang/String;Ljava/lang/Class;IILjava/lang/String;[B)V', $args, NULL, $trace);
		$fields->set($i, $fieldref);
	}
	$trace->pop();
	return $fieldsref;
}
