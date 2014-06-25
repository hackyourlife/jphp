<?php

class FileInputStream extends InputStream {
	private	$file = NULL;

	public function __construct($filename) {
		$this->file = fopen($filename, 'rb');
	}

	public function __destruct() {
		$this->close();
	}

	public function close() {
		if($this->file != NULL)
			fclose($this->file);
		$this->file = NULL;
	}

	public function read($count = 1) {
		if($count <= 0) {
			throw new Exception("invalid read count: $count");
		}
		return fread($this->file, $count);
	}
}
