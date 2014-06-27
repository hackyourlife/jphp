<?php

function Java_java_lang_String_intern(&$jvm, &$class, $args, $trace) {
	return JavaString::intern($jvm, $class->getReference());
}
