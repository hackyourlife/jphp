<?php

class StringInputStream extends InputStream {
	private	$string;
	private	$index;

	public function __construct($string) {
		$this->string = $string;
		$this->index = 0;
	}

	public function read($length = 1) {
		if($this->index > strlen($this->string))
			throw new Exception('reading behind EOF');
		if($length == 1)
			return $this->string[$this->index++];
		$value = substr($this->string, $this->index, $length);
		$this->index += $length;
		return $value;
	}

	public function close() {
	}
}
