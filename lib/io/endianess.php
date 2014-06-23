<?php

function get16bit_BE($bytes, $offset = 0) {
	return ($bytes[$offset] << 8) | $bytes[$offset + 1];
}

function get32bit_BE($bytes, $offset = 0) {
	return ($bytes[$offset] << 24) | ($bytes[$offset + 1] << 16) | ($bytes[$offset + 2] << 8) | $bytes[$offset + 3];
}
