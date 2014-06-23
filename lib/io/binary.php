<?php

function str2bin($str) {
	$bin = array();
	for($i = 0; $i < strlen($str); $i++)
		$bin[] = ord($str[$i]);
	return $bin;
}
