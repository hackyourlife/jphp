<?php

class JavaArray {
	private $array = array();
	public $length;
	public function __construct($length, $type) {
		$this->length = $length;
	}
	public function get($index) {
		return $this->array[$index];
	}
	public function set($index, $value) {
		$this->array[$index] = $value;
	}
}
