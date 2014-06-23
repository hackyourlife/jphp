<?php

class File {
	private $filename;

	public function __construct($filename) {
		$this->filename = $filename;
	}

	public function exists() {
		return file_exists($this->filename);
	}

	public function length() {
		return filesize($this->filename);
	}
}
