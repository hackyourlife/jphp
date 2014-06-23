<?php

class JavaArray {
	private $array = array();
	public function get($index) {
		return $this->array[$index];
	}
	public function set($index, $value) {
		$this->array[$index] = $value;
	}
}
