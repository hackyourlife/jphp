<?php

function Java_sun_reflect_NativeConstructorAccessorImpl_newInstance0(&$jvm, &$class, $args, $trace) {
	$constructor = $jvm->references->get($args[0]);
	$args = $args[1];
	if($args !== NULL) {
		throw new Exception('not implemented');
	}
	$trace->push('sun/reflect/NativeConstructorAccesorImpl', 'newInstance0', 0, true);
	$clazz = $jvm->references->get($constructor->getField('clazz'));
	$classname = $clazz->info->name;
	$signaturechars = $jvm->references->get($constructor->getField('signature'))->getField('value');
	$signature = $jvm->references->get($signaturechars)->string();
	$instance = $jvm->instantiate($classname);
	$instanceref = $jvm->references->newref();
	$jvm->references->set($instanceref, $instance);
	$instance->setReference($instanceref);
	$instance->callSpecial('<init>', $signature, NULL, NULL, $trace);
	$trace->pop();
	return $instanceref;
}
