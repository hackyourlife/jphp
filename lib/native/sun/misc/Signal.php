<?php

function Java_sun_misc_Signal_findSignal(&$jvm, &$class, $args, $trace) {
	$signals = array(
		'HUP'	=> 1,
		'INT'	=> 2,
		'QUIT'	=> 3,
		'ILL'	=> 4,
		'ABRT'	=> 6,
		'FPE'	=> 8,
		'KILL'	=> 9,
		'SEGV'	=> 11,
		'PIPE'	=> 13,
		'ALRM'	=> 14,
		'TERM'	=> 15
	);

	$namechars = $jvm->references->get($args[0])->getField('value');
	$name = $jvm->references->get($namechars)->string();
	return $signals[$name];
}

function Java_sun_misc_Signal_handle0(&$jvm, &$class, $args, $trace) {
	return;
}
