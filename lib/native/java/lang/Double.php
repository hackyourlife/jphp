<?php

function Java_java_lang_Double_doubleToRawLongBits(&$jvm, &$class, $args) {
	$x = unpack('L*', pack('d', 1.0));
	$check = 0x12345678;
	$test = unpack('N', pack('I', $check));
	if($test[1] != $check) { // little endian
		$result = ($x[2] << 32) | $x[1];
	} else { // big endian
		$result = ($x[1] << 32) | $x[2];
	}
	return $result;
}

function Java_java_lang_Double_longBitsToDouble(&$jvm, &$class, $args) {
	return f64($args[0]);
}
