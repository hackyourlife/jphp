<?php

function str2bin($str) {
	$bin = array();
	for($i = 0; $i < strlen($str); $i++)
		$bin[] = ord($str[$i]);
	return $bin;
}

function s8($u8) {
	$sign = $u8 & 0x80;
	if($sign) {
		return -(((~$u8) & 0xFF) + 1);
	} else {
		return $u8 & 0xFF;
	}
}

function s16($u16) {
	$sign = $u16 & 0x8000;
	if($sign) {
		return -(((~$u16) & 0xFFFF) + 1);
	} else {
		return $u16 & 0xFFFF;
	}
}

function s32($u32) {
	$sign = $u32 & 0x80000000;
	if($sign) {
		return -(((~$u32) & 0xFFFFFFFF) + 1);
	} else {
		return $u32 & 0xFFFFFFFF;
	}
}

function f32($bits) {
	$s = (($bits >> 31) == 0) ? 1 : -1;
	$e = (($bits >> 23) & 0xff);
	$m = ($e == 0) ?
		($bits & 0x7fffff) << 1 :
		($bits & 0x7fffff) | 0x800000;
	return $s * $m * pow(2, $e - 150);
}

function f64($bits) {
	$s = (($bits >> 63) == 0) ? 1 : -1;
	$e = (int)(($bits >> 52) & 0x7ff);
	$m = ($e == 0) ?
		($bits & 0xfffffffffffff) << 1 :
		($bits & 0xfffffffffffff) | 0x10000000000000;
	return $s * $m * pow(2, $e - 1075);
}
