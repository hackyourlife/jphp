<?php

function Java_org_hackyourlife_server_ServletOutputStreamImpl_write(&$jvm, &$class, $args, $trace) {
	print(chr($args[0]));
}

function Java_org_hackyourlife_server_ServletOutputStreamImpl_write0(&$jvm, &$class, $args, $trace) {
	$bytes = $jvm->references->get($args[0]);
	$offset = $args[1];
	$length = $args[2];
	print($bytes->bstring($offset, $length));
}
