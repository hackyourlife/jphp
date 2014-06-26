<?php

function Java_java_lang_Float_floatToRawIntBits(&$jvm, &$class, $args) {
	$x = unpack('I', pack('f', $args[0]));
	return $x[1];
}
