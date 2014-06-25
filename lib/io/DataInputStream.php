<?php

class DataInputStream extends InputStream {
	private	$stream = NULL;

	public function __construct($stream) {
		$this->stream = $stream;
	}

	public function __destruct() {
		$this->close();
	}

	public function close() {
		if($this->stream != NULL)
			$this->stream->close();
		$this->stream = NULL;
	}

	public function read($length = 1) {
		return $this->stream->read($length);
	}

	public function readByte() {
		return ord($this->read(1));
	}

	public function readShort() {
		return get16bit_BE(str2bin($this->read(2)));
	}

	public function readInt() {
		return get32bit_BE(str2bin($this->read(4)));
	}
}
